import { Component, ViewChild, AfterViewInit } from '@angular/core';
import {Screenshot, ScreenshotsBlock} from "../../../models/screenshot.model";
import {DashboardService} from "../dashboard.service";


@Component({
  selector: 'dashboard-screenshotlist',
  templateUrl: './screenshot.list.component.html',
  styleUrls: ['./screenshot.list.component.css']
})
export class ScreenshotListComponent {

    @ViewChild('loading') element: any;
    chunksize: number = 32;
    offset: number = 0;
    blocks: ScreenshotsBlock[] = [];
    screenshotLoading: boolean = false;

    constructor(protected dashboardService: DashboardService) {
    }


    ngOnInit() {
      window.addEventListener('scroll', this.onScrollDown.bind(this), true);
      this.loadNext();
    }


    loadNext() {

        if(this.screenshotLoading) {
          return;
        }

        this.screenshotLoading = true;
        this.dashboardService.getScreenshots(this.chunksize, this.offset,this.setData.bind(this));
    }

    setData(result) {

        this.offset += this.chunksize;


        for(let block of result) {
          this.blocks.push(new ScreenshotsBlock(block));
        }

        this.screenshotLoading = false;

    }


    ngAfterViewInit() {
      let htmlElement = this.element.nativeElement;
      let height = this.element.nativeElement.offsetHeight;

      console.log(height);
    }


    onScrollDown() {
      let block_Y_position = this.element.nativeElement.offsetTop;
      let scroll_Y_top_position = window.scrollY;
      let windowHeight = window.innerHeight;

      let bottom_scroll_Y_position = scroll_Y_top_position + windowHeight;


      if(bottom_scroll_Y_position < block_Y_position) { // loading new screenshots doesn't needs
        return;
      }

      this.loadNext();

    }

    hour(datetime) {
      let regex = /(\d{4}-\d{2}-\d{2}) (\d{2}):\d{2}:\d{2}/;
      let matches = datetime.match(regex);

      return matches[2] + ':00 ' + matches[1];
    }

    minutes(datetime) {
      let regex = /\d{4}-\d{2}-\d{2} \d{2}:(\d{2}:\d{2})/;
      let matches = datetime.match(regex);

      return matches[1];
    }
}
