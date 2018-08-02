import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from '../../api/api.service';

@Injectable()
export class ReportProjectsService {
    constructor(api: ApiService) {
    }
}
