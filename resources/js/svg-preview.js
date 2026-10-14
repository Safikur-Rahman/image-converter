export function svgPreviewComponent(options = {}) {
    return {
        zoom: options.initialZoom ?? 1,
        originX: options.initialX ?? 0,
        originY: options.initialY ?? 0,
        minZoom: 0.05,
        maxZoom: 20,

        get transform() {
            return `translate(${this.originX}px, ${this.originY}px) scale(${this.zoom})`;
        },

        zoomIn() {
            this.zoom = Math.min(this.maxZoom, this.zoom * 1.25);
        },

        zoomOut() {
            this.zoom = Math.max(this.minZoom, this.zoom / 1.25);
        },

        reset(initialDimensions) {
            this.zoom = initialDimensions?.zoom ?? 1;
            this.originX = initialDimensions?.x ?? 0;
            this.originY = initialDimensions?.y ?? 0;
        },

        fit() {
            const container = this.$refs.viewport;
            const svg = this.$refs.svg;

            if (!container || !svg) return;

            const pad = 40;
            const cw = container.clientWidth - pad;
            const ch = container.clientHeight - pad;

            let naturalW, naturalH;
            if (svg.tagName?.toLowerCase() === 'img') {
                naturalW = svg.naturalWidth;
                naturalH = svg.naturalHeight;
            } else {
                const parts = (svg.getAttribute('viewBox') || '').split(' ').map(Number);
                naturalW = (parts.length === 4 ? parts[2] : svg.width?.baseVal?.value) || 100;
                naturalH = (parts.length === 4 ? parts[3] : svg.height?.baseVal?.value) || 100;
            }

            if (!naturalW || !naturalH || naturalW <= 0 || naturalH <= 0) return;

            this.zoom = Math.min(cw / naturalW, ch / naturalH, 1);
            this.zoom = Math.max(this.minZoom, this.zoom);
            this.originX = (container.clientWidth - naturalW * this.zoom) / 2;
            this.originY = (container.clientHeight - naturalH * this.zoom) / 2;
        },

        dragStart(event) {
            if (event.button !== 0) return;
            event.preventDefault();

            const startX = event.clientX;
            const startY = event.clientY;
            const startOx = this.originX;
            const startOy = this.originY;

            const move = (e) => {
                this.originX = startOx + (e.clientX - startX);
                this.originY = startOy + (e.clientY - startY);
            };

            const up = () => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
                this.$refs.viewport?.classList.remove('dragging');
            };

            this.$refs.viewport?.classList.add('dragging');
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
            // Remove any lingering listeners on teardown.
            this._cleanupDrag = up;
        },
    };
}

window.svgPreviewComponent = svgPreviewComponent;