/***********\
     申明    |
\***********/
    addEvent(document,'DOMContentLoaded',function () {

        SRGlobal = {

            Window:{
                Width: document.documentElement.clientWidth || document.body.clientWidth,
                Height: document.documentElement.clientHeight || document.body.clientHeight,
                Scroll:{
                    DirX:'',
                    DirY:'',
                    Top:0,
                    Left:0,
                    BeforeTop:0,
                    BeforeLeft:0,
                    ToBottom:0,
                },
            },

            Cursor:{
                type:'', //游标类型

                Top:0,
                Left:0,
                BeforeTop:0,
                BeforeLeft:0,

                click:{
                    Top:0,
                    Left:0,
                    BeforeTop:0,
                    BeforeLeft:0,
                },
                down:{
                    Top:0,
                    Left:0,
                    BeforeTop:0,
                    BeforeLeft:0,
                },
                up:{
                    Top:0,
                    Left:0,
                    BeforeTop:0,
                    BeforeLeft:0,
                }
            },

            Wheel:{
                Dir:'',
            },

            Temp: {
                windowSize:''
            }
        };

        loader = {
            pare : SR('render[role="loader"]'),
            hook : SR('#loader_hook')[0]
        };

        body = SR('render[role="body"]')[0];
        nav = SR('[html-header] #nav')[0];
        banner = SR('[html-header] .banner')[0];
        uconsole = SR('#console')[0];
        bottommenu = SR('#bottommenu')[0];

        MasElements = {
            pare:            SR('.Mas')[0],
            guide:           SR('.Mas > .mas-guide')[0],
            guideSwitch:     SR('.Mas > .mas-guide > .mas-guide-switch')[0],
            viewer:          SR('.Mas > .mas-viewer')[0],
            viewerHeader:    SR('.Mas > .mas-viewer > .mas-viewer-header')[0],
            viewerBanner:    SR('.Mas > .mas-viewer > .mas-viewer-header > div > .banner')[0],
            viewerBannerImg: SR('.Mas > .mas-viewer > .mas-viewer-header > div > .banner > img')[0],
            Bank:            SR('.Mas > .mas-viewer > .mas-viewer-header  > div > .bank')[0],
            BankS:           SR('.Mas > .mas-viewer > .mas-viewer-header  > div > .bank.type-scroll')[0],

            MasViewerScroll: {
                Top: 0,
                Left: 0,
                BeforeTop: 0,
                BeforeLeft: 0,
            }
        };

        DocAction.initalize();
        MasAction.initalize();
    });

    var Misc = {
        Debug: function (){
            console.log(SRGlobal);
        },
        WinScrollDirRefresh: function (tmp, obj){
            fastdom.measure(() => {
                tmp.Top = obj.scrollTop;
                tmp.Left = obj.scrollLeft;

                SRGlobal.Window.Scroll.Top = tmp.Top;
                SRGlobal.Window.Scroll.Left = tmp.Left;

                if (tmp.Top > tmp.BeforeTop) {
                    SRGlobal.Window.Scroll.DirY = 'down';
                } else {
                    SRGlobal.Window.Scroll.DirY = 'up';
                }

                if (tmp.Left > tmp.BeforeLeft) {
                    SRGlobal.Window.Scroll.DirX = 'right';
                } else {
                    SRGlobal.Window.Scroll.DirX = 'left';
                }

                tmp.BeforeTop = tmp.Top;
                tmp.BeforeLeft = tmp.Left;
            });
        }
    };

/***********\
   页面动作  |页面滚动/点击触发的动作
\***********/
    var DocAction = {

        initalize: function () {

            addEvent(window,  'scroll',    DocAction.scroll);
            addEvent(window,  'resize',    DocAction.resize);
            addEvent(document,'mousewheel',DocAction.mousewheel);
            addEvent(document,'mousemove', DocAction.mousemove);
            addEvent(document,'mousedown', DocAction.mousedown);
            addEvent(document,'mouseup',   DocAction.mouseup);
            addEvent(document,'click',     DocAction.mouseclick);

            if(document.addEventListener) { //firefox
                document.addEventListener('DOMMouseScroll', DocAction.mousewheel(), false);
            }

            loader.hook.onload = function () {
                /* tooltip初始化 */
                tooltip_init();

                /* nav样式初始化 */
                if(banner){
                    (document.documentElement.scrollTop || document.body.scrollTop || 0) >= (banner.Css.height - nav.Css.height) ?
                        fastdom.mutate(() => {body.addClass('scroll-overhead');}) : '';
                }

                /* 页面尺寸初始化 */
                DocAction.resize();

                /* 清除loader遮罩 */
                for(let self of loader.pare){
                    fastdom.mutate(() => {self.parentElement.removeChild(self)});
                }
            };

            loader.hook.src = loader.hook.data('src');
        },

        resize: function () {
            let MasGuideWidth = isUndefined(MasElements.guide) ? 0 : 72,
                windowSize = 'WL-0';

            fastdom.measure(() => {
                SRGlobal.Window.Height = document.documentElement.clientHeight || document.body.clientHeight;
                SRGlobal.Window.Width = document.documentElement.clientWidth || document.body.clientWidth;

                if (SRGlobal.Window.Width > 4080) {
                    windowSize = 'WL-4K';
                } else if (SRGlobal.Window.Width > 2550) {
                    windowSize = 'WL-2K';
                } else if (SRGlobal.Window.Width > 1910) {
                    windowSize = 'WL-1K';
                } else if (SRGlobal.Window.Width > 1320) {
                    windowSize = 'WL-13P';
                } else if (SRGlobal.Window.Width > 760) {
                    windowSize = 'WL-7P';
                } else if (SRGlobal.Window.Width > 470 + MasGuideWidth) {
                    windowSize = 'WL-4P';
                } else if (SRGlobal.Window.Width > 350 + MasGuideWidth) {
                    windowSize = 'WL-3P';
                }

                SRGlobal.Temp.windowSize = windowSize;
            });

            if (SRGlobal.Window.Height >= SRGlobal.Window.Width) {
                fastdom.mutate(() => {body.addClass('WD-V')});
            } else {
                fastdom.mutate(() => {body.delClass('WD-V')});
            }

            fastdom.mutate(() => {
                body.delClass("WL-0 WL-3P WL-4P WL-7P WL-13P WL-1K WL-2K WL-4K");
                body.addClass(SRGlobal.Temp.windowSize);
            });

            fastdom.mutate(() => {
                if(nav) nav.delClass('trans-ease-slow trans-ease');
                if(document.querySelector('#e_controls')) document.querySelector('#e_controls').delClass('trans-ease-slow trans-ease');
                if(document.querySelector('#console')) document.querySelector('#console').delClass('trans-ease-slow trans-ease');
            });
        },

        scroll: function () {

            Misc.WinScrollDirRefresh(SRGlobal.Window.Scroll, document.documentElement);

            SRGlobal.Window.Scroll.ToBottom = SRGlobal.Window.Scroll.Top + SRGlobal.Window.Height >= SRGlobal.Window.Scroll.Height ? 1 : 0;

            if(SRGlobal.Window.Scroll.ToBottom){
                fastdom.mutate(() => {body.addClass('scroll-tobottom')});
            } else {
                fastdom.mutate(() => {body.delClass('scroll-tobottom')});
            }

            if(banner){
                SRGlobal.Window.Scroll.Top >= (banner.Css.height - nav.Css.height) ?
                    fastdom.mutate(() => {body.addClass('scroll-overhead')}) : fastdom.mutate(() => {body.delClass('scroll-overhead')});
            }

            //nav 滚动效果
            (function () {
                if(nav){
                    fastdom.mutate(() => {nav.addClass('trans-ease')});

                    if(SRGlobal.Window.Scroll.Top > banner.clientHeight && SRGlobal.Window.Scroll.DirY === 'down'){
                        fastdom.mutate(() => {nav.addClass('sleep')});
                    } else {
                        fastdom.mutate(() => {nav.delClass('sleep')});
                    }
                    if(SRGlobal.Window.Scroll.ToBottom && !uconsole){
                        fastdom.mutate(() => {nav.delClass('sleep')});
                    }
                }
            })();

            // editor nav滚动效果
            (function () {
                let editor_nav = document.querySelector('#e_controls');

                if(editor_nav){
                    editor_nav.addClass('trans-ease');
                    if(SRGlobal.Window.Scroll.Top > banner.clientHeight && SRGlobal.Window.Scroll.DirY === 'down'){
                        fastdom.mutate(() => {editor_nav.style.transform ='translateY(-' + nav.Css.height +'px)';});
                    } else {
                        fastdom.mutate(() => {editor_nav.style.transform ='translateY(0)';});
                    }

                    if(SRGlobal.Window.Scroll.ToBottom){
                        fastdom.mutate(() => {editor_nav.style.transform ='translateY(0)';});
                    }
                }
            })();

            // console 翻滚效果
            (function () {
                if(uconsole){
                    let scrollrate = 1.3,
                        menu = uconsole.querySelector('.menu'),
                        menu_list = uconsole.querySelector('.menu > ul'),
                        console_title = uconsole.querySelector('.panel > .title'),
                        console_ctl = uconsole.querySelector('.panel > .panel-container');

                    function topaction () {
                        menu.style.transform = 'translate3d(0,0,0)';
                        menu.style.position = '';
                        menu.style.top = '';
                        console_title.style.position = '';
                        console_title.style.top = '';
                        console_title.style.transform = 'translate3d(0,0,0)';
                        console_ctl.style.paddingTop = '0';
                    }

                    if(uconsole.scrollHeight >= SRGlobal.Window.Height*scrollrate && SRGlobal.Window.Height > 390 ){

                        menu_list.style.height = SRGlobal.Window.Height - console_title.Css.height - menu_list.Css.paddingBottom - ((SRGlobal.Window.Height - uconsole.Css.bottom) > 0 ? (SRGlobal.Window.Height - uconsole.Css.bottom) : 0) + 'px';

                        if(SRGlobal.Window.Scroll.Top > uconsole.Css.top){
                            menu.style.position = 'fixed';
                            menu.style.top = '0';
                            console_title.style.position = 'fixed';
                            console_title.style.top = '0';
                            console_ctl.style.paddingTop = '48px';

                            if(SRGlobal.Window.Scroll.DirY === 'down'){
                                menu.style.transform = 'translate3d(0,0,0)';
                                console_title.style.transform = 'translate3d(0,0,0)';
                            }

                            if(SRGlobal.Window.Scroll.DirY === 'up'){
                                nav.addClass('sleep');
                            }

                        } else {
                            topaction();
                        }

                    }

                    if(SRGlobal.Window.Scroll.Top <= uconsole.Css.top) {
                        topaction();
                    }
                }
            })();
        },

        mousewheel: function (evt) {
            let e = evt || window.event;
            if (e.wheelDelta) {  //判断浏览器IE，谷歌滑轮事件
                if (e.wheelDelta > 0) {SRGlobal.Wheel.Dir="up"}
                if (e.wheelDelta < 0) {SRGlobal.Wheel.Dir="down"}
            } else if (e.detail) {  //Firefox滑轮事件
                if (e.detail> 0) {SRGlobal.Wheel.Dir="down"}
                if (e.detail< 0) {SRGlobal.Wheel.Dir="up"}
            }
        },

        mousemove: function (evt) {
            let e =  evt || window.event;
            let triggle = getEventobj();
            SRGlobal.Cursor.BeforeTop = SRGlobal.Cursor.Top;
            SRGlobal.Cursor.BeforeLeft = SRGlobal.Cursor.Left;
            SRGlobal.Cursor.Top = e.clientY;
            SRGlobal.Cursor.Left = e.clientX;
        },

        mousedown: function (evt) {
            let e =  evt || window.event;
            let triggle = getEventobj();
            SRGlobal.Cursor.down.BeforeTop = SRGlobal.Cursor.down.Top;
            SRGlobal.Cursor.down.BeforeLeft = SRGlobal.Cursor.down.Left;
            SRGlobal.Cursor.down.Top = SRGlobal.Cursor.Top;
            SRGlobal.Cursor.down.Left = SRGlobal.Cursor.Left;
        },

        mouseup: function (evt) {
            let e =  evt || window.event;
            let triggle = getEventobj();
            SRGlobal.Cursor.up.BeforeTop = SRGlobal.Cursor.up.Top;
            SRGlobal.Cursor.up.BeforeLeft = SRGlobal.Cursor.up.Left;
            SRGlobal.Cursor.up.Top = SRGlobal.Cursor.Top;
            SRGlobal.Cursor.up.Left = SRGlobal.Cursor.Left;
        },

        mouseclick: function (evt) {
            let e =  evt || window.event;
            let triggle = getEventobj();
            SRGlobal.Cursor.click.BeforeTop = SRGlobal.Cursor.click.Top;
            SRGlobal.Cursor.click.BeforeLeft = SRGlobal.Cursor.click.Left;
            SRGlobal.Cursor.click.Top = SRGlobal.Cursor.Top;
            SRGlobal.Cursor.click.Left = SRGlobal.Cursor.Left;

            /* modal 在 body 的委托 */
            if(triggle.data('type') === 'modal'){
                triggle.target.modal(triggle.data('action'));
            }
        },
    };

    var MasAction = {

        initalize: function () {
            addEvent(MasElements.viewer,'scroll',MasAction.viewerScroll);
            addEvent(window,'resize',MasAction.bannerImgResize);

            if(MasElements.viewerBannerImg){
                fastdom.mutate(() => {
                    MasElements.viewerBannerImg.style.display = 'none';
                    MasElements.viewerBannerImg.onload = function () {
                        MasAction.bannerImgResize();
                        MasElements.viewerBannerImg.style.display = '';
                    };
                    MasElements.viewerBannerImg.src = MasElements.viewerBannerImg.data('src');
                });
            }
        },

        viewerScroll: function () {

            Misc.WinScrollDirRefresh(MasElements.MasViewerScroll, MasElements.viewer);
            if(MasElements.viewerHeader){
                if(MasElements.MasViewerScroll.Top >= MasElements.viewerHeader.Css.height - MasElements.Bank.Css.height){
                    fastdom.mutate(() => {body.addClass('scroll-overhead');});
                } else {
                    fastdom.mutate(() => {body.delClass('scroll-overhead');});
                }

                if(!body.hasClass('scroll-overhead')){
                    fastdom.mutate(() => {
                        let trspct = (1 - (MasElements.MasViewerScroll.Top / MasElements.viewerBannerImg.Css.height) * 1.5 ) * 50;
                        MasElements.viewerBannerImg.style.transform = 'translate(-50%, -' + trspct + '%)';
                    });
                }
            }
        },

        bannerImgResize: function () {
            if(MasElements.viewerBannerImg){
                if(MasElements.viewerBannerImg.naturalWidth / MasElements.viewerBannerImg.naturalHeight >= MasElements.viewerBanner.Css.width / MasElements.viewerBanner.Css.height){
                    fastdom.mutate(() => {
                        MasElements.viewerBannerImg.style.width = "auto";
                        MasElements.viewerBannerImg.style.height = "100%";
                    });
                } else {
                    fastdom.mutate(() => {
                        MasElements.viewerBannerImg.style.width = "100%";
                        MasElements.viewerBannerImg.style.height = "auto";
                    });
                }
            }
        }
    };
