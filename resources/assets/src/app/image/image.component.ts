import { Component, OnChanges, SimpleChanges, Input } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

import { BehaviorSubject, Observable } from 'rxjs';
import { ApiService } from '../api/api.service';

@Component({
    selector: 'app-image',
    templateUrl: './image.component.html',
    styleUrls: ['./image.component.scss']
})
export class ImageComponent implements OnChanges {
    @Input() private src: string;
    @Input() imageClass: string = '';

    private src$ = new BehaviorSubject(this.src);

    dataUrl$ = this.src$.switchMap(url => this.loadImage(url)).startWith('uploads/none.png');

    constructor(
        private httpClient: HttpClient,
        private domSanitizer: DomSanitizer,
        private api: ApiService,
    ) { }

    ngOnChanges(changes: SimpleChanges) {
        if (changes.src
            && (changes.src.currentValue !== changes.src.previousValue
                || changes.src.firstChange)) {
            this.src$.next(this.src);
        }
    }

    private loadImage(url: string): Observable<any> {
        return this.httpClient
            .get(url, {
                headers: {
                    'Authorization': this.api.getAuthString(),
                },
                responseType: 'blob',
            })
            .map(e => this.domSanitizer.bypassSecurityTrustUrl(URL.createObjectURL(e)))
    }
}
