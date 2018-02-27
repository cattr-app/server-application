import {ModuleWithProviders, NgModule, Optional, SkipSelf} from '@angular/core';

import {CommonModule} from '@angular/common';
import {ApiService} from './api.service';
import {HttpClientModule} from '@angular/common/http';


@NgModule({
    imports: [
        CommonModule,
        HttpClientModule
    ],
})
export class ApiModule {
    constructor(@Optional() @SkipSelf() parentModule: ApiModule) {
        if (parentModule) {
            throw new Error(
                'ApiModule is already loaded. Import it in the AppModule only');
        }
    }

    static forRoot(): ModuleWithProviders {
        return {
            ngModule: ApiModule,
            providers: [
                ApiService
            ]
        };
    }
}
