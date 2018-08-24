import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {ReportProjectsComponent} from './projects/report.projects.component';


export const AuthRoutes: Routes = [
    {path: 'projects', component: ReportProjectsComponent},
    /**
     * @todo add more pages to statistic right here
     */
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
