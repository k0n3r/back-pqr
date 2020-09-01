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
            <div class="timeline-point complete">
                <i class="${this . activeItem . icon}"></i>
            </div>
            <div class="timeline-content" data-action="${this . activeItem . action}" data-url="${this . activeItem . url}">
                <div class="card social-card share full-width">
                    <div class="circle" data-toggle="tooltip" title="Label" data-container="body">
                    </div>
                    <div class="card-header clearfix">
                        <div class="user-pic">
                            <img alt="Profile Image" width="33" height="33"
                                src="${baseUrl + this . activeItem . imgRoute}">
                        </div>
                        <span class="my-auto">
                        ${this . activeItem . userName} (${this . activeItem . login}) - ${this . activeItem . title}
                        </span>
                    </div>
                    <label class="card-description
                        ${!this . activeItem . content ? 'd-none' : ''}">
                        <p class="fs-11">${this . activeItem . content}</p>
                    </label>
                </div>
                <div class="event-date">
                    <small class="fs-12 hint-text">
                        ${this . activeItem . date}
                    </small>
                </div>
            </div>
        </div>`;

        return response;
    }
}


JAVASCRIPT;

echo $code;
