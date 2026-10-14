import './bootstrap';

import Alpine from 'alpinejs';
import { converterComponent } from './converter';
import { svgPreviewComponent } from './svg-preview';
import { conversionStatusComponent } from './conversion-status';

window.Alpine = Alpine;

Alpine.data('converter', converterComponent);
Alpine.data('svgPreview', svgPreviewComponent);
Alpine.data('conversionStatus', conversionStatusComponent);

Alpine.start();