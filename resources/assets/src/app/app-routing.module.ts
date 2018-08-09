import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';

import {IntegrationsComponent} from './pages/integrations/integrations.component';

const routes: Routes = [
    {path: 'projects', loadChildren: './pages/projects/projects.module#ProjectsModule'},
    {path: 'tasks', loadChildren: './pages/tasks/tasks.module#TasksModule'},
    {path: 'statistic', loadChildren: './pages/statistic/statistic.module#StatisticModule'},
    {path: 'users', loadChildren: './pages/users/users.module#UsersModule'},
    {path: 'roles', loadChildren: './pages/roles/roles.module#RolesModule'},
    {path: 'screenshots', loadChildren: './pages/screenshots/screenshots.module#ScreenshotsModule'},
    {path: 'time-intervals', loadChildren: './pages/timeintervals/timeintervals.module#TimeIntervalsModule'},
    {path: 'auth', loadChildren: './auth/auth.module#AuthModule'},
    {path: '', redirectTo: '/dashboard', pathMatch: 'full'},
    {path: 'dashboard', loadChildren: './pages/dashboard/dashboard.module#DashboardModule'},
    {path: 'integrations', component: IntegrationsComponent},
    {path: 'report', loadChildren: './pages/report/report.module#ReportModule'}
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
