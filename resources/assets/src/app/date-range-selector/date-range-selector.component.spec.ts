import { TestBed, async } from '@angular/core/testing';
import { NO_ERRORS_SCHEMA } from '@angular/core';
import { ApiService } from '../api/api.service';
import { HttpClient, HttpHandler } from '@angular/common/http';
import { Router } from '@angular/router';
import { APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy } from '@angular/common';
import { By } from '@angular/platform-browser';
import { LocalStorage } from '../api/storage.model';
import { DateRangeSelectorComponent } from './date-range-selector.component';
import { AppRoutingModule } from '../app-routing.module';
import { ProjectsService } from '../pages/projects/projects.service';
import { AllowedActionsService } from '../pages/roles/allowed-actions.service';
import { TranslateService, TranslateModule, TranslateLoader, TranslateFakeLoader } from '@ngx-translate/core';

describe('Date-range-selector component', () => {
    let component, fixture;

    beforeEach(async(() => {
        TestBed.configureTestingModule({
            declarations: [DateRangeSelectorComponent],
            schemas: [NO_ERRORS_SCHEMA],
            imports: [
                TranslateModule.forRoot({
                    loader: { provide: TranslateLoader, useClass: TranslateFakeLoader }
                }),
            ],
            providers: [
                ApiService,
                HttpClient,
                HttpHandler,
                { provide: Router, useClass: AppRoutingModule },
                Location,
                { provide: LocationStrategy, useClass: PathLocationStrategy },
                { provide: APP_BASE_HREF, useValue: '/' },
                AllowedActionsService,
                ProjectsService,
                TranslateService,
            ]
        })
            .compileComponents().then(() => {
                fixture = TestBed.createComponent(DateRangeSelectorComponent);
                component = fixture.debugElement.componentInstance;
                fixture.detectChanges();
            });
    }));

    it('should be created', async(() => {
        expect(component).toBeTruthy();
    }));

    it('has a prev button', async(() => {
        expect(fixture.debugElement.query(By.css("button[aria-label='prev']"))).not.toBeNull();
    }));

    it('has a next button', async(() => {
        expect(fixture.debugElement.query(By.css("button[aria-label='next']"))).not.toBeNull();
    }));

    it('has a day button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.day")).length).toBe(1);
    }));

    it('has a week button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.week")).length).toBe(1);
    }));

    it('has a month button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.month")).length).toBe(1);
    }));

    it('has a day-range button', async(() => {
        const buttonsDate = fixture.debugElement.queryAll(By.css("div.buttons > button"));
        expect(buttonsDate.filter(button => button.nativeElement.innerHTML.includes("control.date-range")).length).toBe(1);
    }));
});