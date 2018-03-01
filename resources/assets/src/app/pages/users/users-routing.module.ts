import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {UsersCreateComponent} from './create/users.create.component';
import {UsersEditComponent} from './edit/users.edit.component';
import {UsersShowComponent} from './show/users.show.component';
import {UsersListComponent} from './list/users.list.component';

export const AuthRoutes: Routes = [
    {path: 'create', component: UsersCreateComponent},
    {path: 'edit/:id', component: UsersEditComponent},
    {path: 'list', component: UsersListComponent},
    {path: 'show/:id', component: UsersShowComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
