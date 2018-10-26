import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';

const routes: Routes = [
    {path: 'projects', loadChildren: './pages/projects/projects.module#ProjectsModule'},
    {path: 'tasks', loadChildren: './pages/tasks/tasks.module#TasksModule'},
    {path: 'statistic', loadChildren: './pages/statistic/statistic.module#StatisticModule'},
    {path: 'report', loadChildren: './pages/projectsreport/projectsreport.module#ProjectsreportModule'},
    {path: 'time-use-report', loadChildren: './pages/time-use-report/time-use-report.module#TimeUseReportModule'},
    {path: 'users', loadChildren: './pages/users/users.module#UsersModule'},
    {path: 'roles', loadChildren: './pages/roles/roles.module#RolesModule'},
    {path: 'settings', loadChildren: './pages/settings/settings.module#SettingsModule'},
    {path: 'screenshots', loadChildren: './pages/screenshots/screenshots.module#ScreenshotsModule'},
    {path: 'auth', loadChildren: './auth/auth.module#AuthModule'},
    {path: '', redirectTo: '/dashboard', pathMatch: 'full'},
    {path: 'dashboard', loadChildren: './pages/dashboard/dashboard.module#DashboardModule'},
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
