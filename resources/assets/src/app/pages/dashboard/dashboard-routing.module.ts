import { ModuleWithProviders } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { DashboardComponent } from './dashboard.component';

export const AuthRoutes: Routes = [
  { path: '', component: DashboardComponent },
];

export const AuthRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
