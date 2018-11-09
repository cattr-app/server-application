import {TestBed, async} from '@angular/core/testing';
import {NO_ERRORS_SCHEMA} from '@angular/core';
import {ApiService} from '../../api/api.service';
import {HttpClient, HttpHandler} from '@angular/common/http';
import {Router} from '@angular/router';
import {APP_BASE_HREF, Location, LocationStrategy, PathLocationStrategy} from '@angular/common';
import {By} from '@angular/platform-browser';
import {LoginComponent} from './login.component';
import {AppRoutingModule} from '../../app-routing.module';
import {AllowedActionsService} from '../../pages/roles/allowed-actions.service';
import { FormsModule } from '@angular/forms';
import { LoginService } from './login.service';

describe('Login component', () => {
  let component, fixture;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [LoginComponent, ],
      imports: [
        FormsModule,
      ],
      schemas: [NO_ERRORS_SCHEMA],
      providers: [
        ApiService,
        HttpClient,
        HttpHandler,
        {provide: Router, useClass: AppRoutingModule},
        Location,
        {provide: LocationStrategy, useClass: PathLocationStrategy},
        {provide: APP_BASE_HREF, useValue: '/'},
        AllowedActionsService,
        LoginService,
    ]
    })
      .compileComponents().then(() => {
      fixture = TestBed.createComponent(LoginComponent);
      component = fixture.debugElement.componentInstance;
      fixture.detectChanges();
    });
  }));

  it('should be created', async(() => {
    expect(component).toBeTruthy();
  }));

  it('has login', async(() => {
    expect(fixture.debugElement.query(By.css("input#login[type='text'][required]"))).not.toBeNull();
  }));

  it('has passwod', async(() => {
    expect(fixture.debugElement.query(By.css("input#password[type='password'][required]"))).not.toBeNull();
  }));

  it('has submit button', async(() => {
    expect(fixture.debugElement.query(By.css("button[type='submit']"))).not.toBeNull();
  }));

  xit('has error message if submit empty form', async(() => {
    const form = fixture.debugElement.query(By.css("form"));
    console.log(form);
    const submitEvent =  form.listeners.filter(event => event.name == "submit").shift();
    console.log(submitEvent);
    submitEvent.callback.call();
    fixture.detectChanges();
    console.log(fixture.debugElement.query(By.all()).nativeElement);
    expect(fixture.debugElement.query(By.css(".alert")).nativeElement).not.toBeNull();
  }));
});
