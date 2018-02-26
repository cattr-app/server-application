import {Component} from '@angular/core';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.scss'],
})
export class AppComponent {
    title = 'Laravel 5 Angular 4 Demo';

    onChangeTitle(a) {
        console.log(a);
    }
}
