import {ModuleWithProviders} from '@angular/core';
import {Routes, RouterModule} from '@angular/router';
import {ScreenshotsCreateComponent} from './create/screenshots.create.component';
import {ScreenshotsEditComponent} from './edit/screenshots.edit.component';
import {ScreenshotsListComponent} from './list/screenshots.list.component';
import {ScreenshotsShowComponent} from './show/screenshots.show.component';


export const AuthRoutes: Routes = [
    // {path: 'create', component: ScreenshotsCreateComponent},
    {path: 'edit/:id', component: ScreenshotsEditComponent},
    {path: 'list', component: ScreenshotsListComponent},
    {path: 'show/:id', component: ScreenshotsShowComponent},
    {path: '', redirectTo: 'login', pathMatch: 'full'},
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
