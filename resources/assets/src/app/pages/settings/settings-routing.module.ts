import { ModuleWithProviders } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { SettingsComponent } from './settings.component';

export const AuthRoutes: Routes = [
	{ path: '', component: SettingsComponent },
];

export const SettingsRoute: ModuleWithProviders = RouterModule.forChild(AuthRoutes);
