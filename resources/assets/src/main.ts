import { enableProdMode } from '@angular/core';
import { platformBrowserDynamic } from '@angular/platform-browser-dynamic';

import { AppModule } from './app/app.module';
import { environment } from './environments/environment';

if (environment.production) {
  enableProdMode();
}

import * as jQuery from 'jquery';
(window as any).jQuery = (window as any).$ = jQuery;

platformBrowserDynamic().bootstrapModule(AppModule);
