import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {ReportProjectsComponent} from './reportprojects.component';


export const AuthRoutes: Routes = [
    {path: '', component: ReportProjectsComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
