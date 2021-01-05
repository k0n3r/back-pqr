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
        let response='';
        let iconPoint = `<div class="timeline-point small"></div>`;
        if(typeof this.activeItem.iconPoint!='undefined'){
            iconPoint = `<div class="timeline-point \${this.activeItem.iconPointColor}">
                <i class="\${this.activeItem.iconPoint}"></i>
            </div>`;
        }

        let download='';
        if(typeof this.activeItem.url!='undefined'){
            download = `<div class="card-footer clearfix">
                <ul class="reactions">
                    <li>
                        <a href="\${this.activeItem.url}" targe="_blank">Descargar <i class="fa fa-cloud-download"></i></a>
                    </li>
                </ul>
            </div>`;
        }

        let header='';
        if(typeof this.activeItem.header!='undefined'){
            header = `<div class="card-header clearfix">
                <div class="user-pic">
                    <img alt="\${this.activeItem.userName}" src="\${this.activeItem.imgRoute}" width="33" height="33">
                </div>
                <h5>\${this.activeItem.business}</h5>
                <h6>\${this.activeItem.userName}</h6>
            </div>`;
        }

        response = `<div class="timeline-block" style="margin:2em 0">
            \${iconPoint}

            <div class="timeline-content">

                <div class="card social-card share full-width">

                    \${header}

                    <div class="card-description">
                        <p>\${this.activeItem.description}</p>
                    </div>

                    \${download}

                </div>

                <div class="event-date">
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
