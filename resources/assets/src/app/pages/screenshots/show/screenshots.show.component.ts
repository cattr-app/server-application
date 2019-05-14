import {Component, OnInit, OnDestroy} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {ActivatedRoute, Router} from '@angular/router';
import {ScreenshotsService} from '../screenshots.service';
import {ItemsShowComponent} from '../../items.show.component';
import {Screenshot} from '../../../models/screenshot.model';
import {AllowedActionsService} from '../../roles/allowed-actions.service';

@Component({
    selector: 'app-screenshots-show',
    templateUrl: './screenshots.show.component.html',
    styleUrls: ['../../items.component.scss']
})
export class ScreenshotsShowComponent extends ItemsShowComponent implements OnInit, OnDestroy {

    public item: Screenshot = new Screenshot();

    constructor(api: ApiService,
                screenshotService: ScreenshotsService,
                route: ActivatedRoute,
                allowService: AllowedActionsService,
                private router: Router) {
        super(api, screenshotService, route, allowService);
    }

    ngOnInit() {
        this.sub = this.route.params.subscribe(params => {
            this.id = +params['id'];
        });
        const filter = {'with': 'timeInterval,timeInterval.task,timeInterval.user'};

        this.itemService.getItem(this.id, this.setItem.bind(this), filter);
    }

    delete() {
        this.itemService.removeItem(this.id, () => {
            this.router.navigate(['/screenshots/list']);
        });
    }

    cleanupParams() : string[] {
        return [
            'item',
            'api',
            'screenshotService',
            'router',
            'allowService',
        ];
    }

    ngOnDestroy() {
        for (let param of this.cleanupParams()) {
            delete this[param];
        }
    }
}
