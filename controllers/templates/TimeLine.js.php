<?php
$code = <<<JAVASCRIPT
class TimeLine {

    constructor(options) {
        this.options = options;
    }

    init() {
        let list = new String();
        this.sourceData = this.options.source();

        if (!this.sourceData.length) {
            list = 'no data found';
        } else {
            list = this.createTemplate();
        }

        var div = document.createElement('section');
        div.setAttribute('class', 'timeline');
        div.innerHTML = list;

        document.querySelector(this.options.selector).appendChild(div);
    }

    createTemplate() {
        let data = [];

        this.sourceData.forEach(e => {
            this.activeItem = e;
            data.push(this.createItem());
        });

        return data.join('');
    }

    createItem() {

        let response = `<div class="timeline-block" style="margin:2em 0">
            <div class="timeline-point \${this.activeItem.iconPointColor}">
                <i class="\${this.activeItem.iconPoint}"></i>
            </div>
            <div class="timeline-content">
                <div class="card social-card share full-width">
                    <div class="circle" data-toggle="tooltip" title="Label" data-container="body">
                    </div>

                    <div class="card-header clearfix">
                        <div class="user-pic">
                             <i class="\${this.activeItem.iconProfile}"></i>
                        </div>
                        <h5>\${this.activeItem.business}</h5>
                        <h6>\${this.activeItem.userName}</h6>
                      </div>
                      <div class="card-description">
                        <p>\${this.activeItem.description}</p>
                      </div>
                </div>
                <div class="event-date">
                    <h6 class="font-montserrat all-caps hint-text m-t-0">Apple Inc</h6>
                    <small class="fs-12 hint-text">
                        \${this.activeItem.date}
                    </small>
                </div>
            </div>
        </div>`;

        return response;
    }
}


JAVASCRIPT;

echo $code;
