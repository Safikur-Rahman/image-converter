const STEP_ORDER = ['pending', 'processing', 'background_removing', 'vectorizing', 'optimizing'];

export function conversionStatusComponent(options = {}) {
    return {
        uuid: options.uuid ?? '',
        started: options.started ?? true,
        pollTimer: null,
        terminal: options.terminal ?? false,
        failed: false,
        statusLabel: 'Preparing…',
        currentStep: 0,
        statusError: '',

        init() {
            if (this.started && !this.terminal) {
                this.poll();
            }

            if (this.terminal) {
                this.applyStatus('completed');
                this.failed = true;
            }
        },

        destroy() {
            if (this.pollTimer) {
                clearTimeout(this.pollTimer);
            }
        },

        poll() {
            if (this.terminal) return;

            fetch(`/conversions/${this.uuid}/status`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((res) => (res.ok ? res.json() : Promise.reject(new Error('bad response'))))
                .then((payload) => {
                    const data = payload.data ?? payload;
                    this.applyStatus(data.status);

                    if (data.status === 'completed' || data.status === 'failed' || data.status === 'cancelled') {
                        this.terminal = true;
                        if (data.status === 'failed') {
                            this.failed = true;
                            this.statusError = data.error_message || 'The conversion failed unexpectedly.';
                        }
                        if (data.status === 'completed') {
                            window.location.reload();
                            return;
                        }
                        return;
                    }

                    if (!this.terminal) {
                        this.pollTimer = setTimeout(() => this.poll(), 1800);
                    }
                })
                .catch(() => {
                    if (!this.terminal) {
                        this.pollTimer = setTimeout(() => this.poll(), 3000);
                    }
                });
        },

        applyStatus(status) {
            this.statusLabel = {
                pending: 'Queued…',
                processing: 'Processing…',
                background_removing: 'Removing background…',
                vectorizing: 'Tracing the vector path…',
                optimizing: 'Optimizing the SVG file…',
                completed: 'Completed.',
                failed: 'Failed.',
                cancelled: 'Cancelled.',
            }[status] ?? this.statusLabel;

            const idx = STEP_ORDER.indexOf(status);
            if (idx !== -1) {
                this.currentStep = Math.max(idx - 1, 0);
            }

            if (status === 'completed') {
                this.currentStep = 4;
            }
        },
    };
}

window.conversionStatusComponent = conversionStatusComponent;