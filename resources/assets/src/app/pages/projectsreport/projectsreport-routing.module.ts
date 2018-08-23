import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {ProjectsreportComponent} from './time/projectsreport.component';


export const AuthRoutes: Routes = [
    {path: '', component: ProjectsreportComponent},
    /**
     * @todo add more pages to statistic right here
     */
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
