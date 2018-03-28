import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {RolesListComponent} from './list/roles.list.component';
import {RolesCreateComponent} from './create/roles.create.component';
import {RolesEditComponent} from './edit/roles.edit.component';

export const RolesRoutes: Routes = [
    {path: 'list', component: RolesListComponent},
    {path: 'create', component: RolesCreateComponent},
    {path: 'edit/:id', component: RolesEditComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const RolesRoute: ModuleWithProviders = RouterModule.forChild(RolesRoutes);
