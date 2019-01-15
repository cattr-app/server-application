import {EventEmitter, Injectable, Output} from '@angular/core';
import {ApiService} from '../../../api/api.service';
import {TimeInterval} from '../../../models/timeinterval.model';
import {ItemsService} from '../../items.service';

@Injectable()
export class ProjectReportService extends ItemsService {

  constructor(api: ApiService) {
    super(api);
  }

  getApiPath() {
    return `project-report`;
  }

  getProjects(uids) {
    const params = {
      uids: uids
    };
    return new Promise((resolve) => {
      this.api.send(this.getApiPath() + '/projects', params, (result => {
        resolve(result);
      }));
    });
  }

  getTaskDates(uid, tid, start_at, end_at) {
    const params = { uid, start_at, end_at };
    return new Promise<{ date: string, duration: number }[]>((resolve) => {
      this.api.send(`${this.getApiPath()}/list/tasks/${tid}`, params, (result => {
        resolve(result);
      }));
    });
  }

  convertFromApi(itemFromApi) {
    return itemFromApi;
  }
}
