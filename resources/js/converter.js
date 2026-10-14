const ACCEPTED = ['png', 'jpg', 'jpeg', 'webp'];

export function converterComponent(options = {}) {
    const maxFileSizeBytes = options.maxBytes ?? 10 * 1024 * 1024;
    const maxDimensions = options.maxDims ?? 4096;

    return {
        dragActive: false,
        fileName: '',
        fileSize: 0,
        fileExt: '',
        file: null,
        conversionType: 'color',
        quality: 'high',
        pathOptimization: 'balanced',
        removeBackground: false,
        uploading: false,
        uploadProgress: 0,
        uploadError: '',
        maxFileSizeBytes,
        maxDimensions,

        get canUpload() {
            return this.file !== null && !this.uploading;
        },

        get fileSizeText() {
            if (!this.fileSize) return '';
            return (this.fileSize / 1024 / 1024).toFixed(2) + ' MB';
        },

        handleDrop(event) {
            this.dragActive = false;
            const files = event.dataTransfer?.files;
            if (files && files.length) this.handleFile(files[0]);
        },

        handleFile(file) {
            this.uploadError = '';
            if (!file) return;

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!ACCEPTED.includes(ext)) {
                this.file = null;
                this.uploadError = 'Unsupported file type. Please upload a PNG, JPG or WEBP image.';
                this.fileName = '';
                return;
            }

            if (file.size > this.maxFileSizeBytes) {
                this.file = null;
                this.uploadError = `File is too large. Maximum allowed size is ${(this.maxFileSizeBytes / 1024 / 1024).toFixed(1)} MB.`;
                this.fileName = '';
                return;
            }

            const img = new Image();
            const objectUrl = URL.createObjectURL(file);
            img.onload = () => {
                URL.revokeObjectURL(objectUrl);
                if (img.naturalWidth > this.maxDimensions || img.naturalHeight > this.maxDimensions) {
                    this.file = null;
                    this.uploadError = `Image exceeds the maximum dimensions of ${this.maxDimensions}px.`;
                    this.fileName = '';
                    this.$refs.fileInput.value = '';
                    return;
                }
                this.file = file;
                this.fileName = file.name;
                this.fileSize = file.size;
                this.fileExt = ext;
            };
            img.onerror = () => {
                URL.revokeObjectURL(objectUrl);
                this.file = null;
                this.uploadError = 'This file does not look like a valid image.';
                this.fileName = '';
                this.$refs.fileInput.value = '';
            };
            img.src = objectUrl;
        },

        resetFile() {
            this.file = null;
            this.fileName = '';
            this.fileSize = 0;
            this.fileExt = '';
            this.uploadError = '';
            this.$refs.fileInput.value = '';
        },

        submitUpload() {
            if (!this.canUpload) return;

            this.uploading = true;
            this.uploadProgress = 0;
            this.uploadError = '';

            const form = this.$refs.form || document.getElementById('converter-form');
            const data = new FormData(form);
            data.set('image', this.file, this.file.name);
            data.set('remove_background', this.removeBackground ? '1' : '0');
            data.set('conversion_type', this.conversionType);
            data.set('quality', this.quality);
            data.set('path_optimization', this.pathOptimization);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                }
            });

            xhr.onload = () => {
                this.uploading = false;
                let payload = null;
                try {
                    payload = JSON.parse(xhr.responseText);
                } catch {
                    payload = null;
                }

                if (xhr.status === 200 && payload?.data?.uuid) {
                    window.location.href = `/conversions/${payload.data.uuid}?started=1`;
                    return;
                }

                if (xhr.status === 422 && payload?.errors) {
                    const first = Object.values(payload.errors)[0];
                    this.uploadError = Array.isArray(first) ? first[0] : String(first);
                    // Show legacy non-JSON validation errors (image upload failure).
                    if (payload?.errors?.image === undefined) {
                        this.uploadError = payload?.message || this.uploadError;
                    }
                    return;
                }

                this.uploadError = payload?.message || 'Something went wrong while uploading. Please try again.';
            };

            xhr.onerror = () => {
                this.uploading = false;
                this.uploadError = 'Network error. Please check your connection and try again.';
            };

            xhr.send(data);
        },
    };
}

window.converterComponent = converterComponent;