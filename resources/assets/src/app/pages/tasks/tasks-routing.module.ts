import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {TasksCreateComponent} from './create/tasks.create.component';
import {TasksEditComponent} from './edit/tasks.edit.component';
import {TasksShowComponent} from './show/tasks.show.component';
import {TasksListComponent} from './list/tasks.list.component';

export const AuthRoutes: Routes = [
    {path: 'create', component: TasksCreateComponent},
    {path: 'edit', component: TasksEditComponent},
    {path: 'list', component: TasksListComponent},
    {path: 'show', component: TasksShowComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
