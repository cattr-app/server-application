import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';

import {DashboardComponent} from './dashboard/dashboard.component';

const routes: Routes = [
    {path: 'projects', loadChildren: './pages/projects/projects.module#ProjectsModule'},
    {path: 'tasks', loadChildren: './pages/tasks/tasks.module#TasksModule'},
    {path: 'users', loadChildren: './pages/users/users.module#UsersModule'},
    {path: 'roles', loadChildren: './pages/roles/roles.module#RolesModule'},
    {path: 'screenshots', loadChildren: './pages/screenshots/screenshots.module#ScreenshotsModule'},
    {path: 'timeintervals', loadChildren: './pages/timeintervals/timeintervals.module#TimeIntervalsModule'},
    {path: 'auth', loadChildren: './auth/auth.module#AuthModule'},
    {path: '', redirectTo: '/dashboard', pathMatch: 'full'},
    {path: 'dashboard', component: DashboardComponent}
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
