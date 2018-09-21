import { ModuleWithProviders } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';

import { TimeUseReportComponent } from './component/time-use-report.component';

export const AuthRoutes: Routes = [
    { path: '', component: TimeUseReportComponent },
    { path: '', redirectTo: 'login', pathMatch: 'full' },
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
