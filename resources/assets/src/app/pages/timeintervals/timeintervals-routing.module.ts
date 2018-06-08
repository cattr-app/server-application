import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {TimeIntervalsCreateComponent} from './create/timeintervals.create.component';
import {TimeIntervalsEditComponent} from './edit/timeintervals.edit.component';
import {TimeIntervalsListComponent} from './list/timeintervals.list.component';
import {TimeIntervalsShowComponent} from './show/timeintervals.show.component';

export const AuthRoutes: Routes = [
    // {path: 'create', component: TimeIntervalsCreateComponent},
    // {path: 'edit/:id', component: TimeIntervalsEditComponent},
    {path: 'list', component: TimeIntervalsListComponent},
    {path: 'show/:id', component: TimeIntervalsShowComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
