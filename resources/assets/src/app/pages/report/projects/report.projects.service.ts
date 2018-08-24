import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeInterval} from '../../../models/timeinterval.model';
import {ItemsService} from '../../items.service';

@Injectable()
export class ProjectReportService extends ItemsService {

  constructor(api: ApiService) {
    super(api);
  }

  getApiPath(action: string = 'list') {
    return `statistic/project-report/${action}`;
  }

  convertFromApi(itemFromApi) {
    return itemFromApi;
  }
}
