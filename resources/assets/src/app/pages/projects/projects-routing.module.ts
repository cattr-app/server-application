import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {ProjectsCreateComponent} from './create/projects.create.component';
import {ProjectsEditComponent} from './edit/projects.edit.component';
import {ProjectsShowComponent} from './show/projects.show.component';
import {ProjectsListComponent} from './list/projects.list.component';

export const AuthRoutes: Routes = [
    {path: 'create', component: ProjectsCreateComponent},
    {path: 'edit/:id', component: ProjectsEditComponent},
    {path: 'list', component: ProjectsListComponent},
    {path: 'show/:id', component: ProjectsShowComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
