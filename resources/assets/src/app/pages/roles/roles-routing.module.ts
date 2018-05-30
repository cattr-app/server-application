import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {RolesListComponent} from './list/roles.list.component';
import {RolesCreateComponent} from './create/roles.create.component';
import {RolesEditComponent} from './edit/roles.edit.component';
import {RolesShowComponent} from './show/roles.show.component';
import {RolesUsersComponent} from './users/roles.users.component';

export const RolesRoutes: Routes = [
    {path: 'list', component: RolesListComponent},
    {path: 'create', component: RolesCreateComponent},
    {path: 'edit/:id', component: RolesEditComponent},
    {path: 'show/:id', component: RolesShowComponent},
    {path: 'users/:id', component: RolesUsersComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const RolesRoute: ModuleWithProviders = RouterModule.forChild(RolesRoutes);
