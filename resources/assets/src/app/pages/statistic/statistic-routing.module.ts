import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {StatisticTimeComponent} from './time/statistic.time.component';


export const AuthRoutes: Routes = [
    {path: 'time', component: StatisticTimeComponent},
    /**
     * @todo add more pages to statistic right here
     */
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
