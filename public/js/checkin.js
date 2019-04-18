function getDeviceInfo() {
    'use strict';

    var module = {
        options: [],
        header: [navigator.platform, navigator.userAgent, navigator.appVersion, navigator.vendor, window.opera],
        dataos: [{
                name: 'Windows Phone',
                value: 'Windows Phone',
                version: 'OS'
            },
            {
                name: 'Windows',
                value: 'Win',
                version: 'NT'
            },
            {
                name: 'iPhone',
                value: 'iPhone',
                version: 'OS'
            },
            {
                name: 'iPad',
                value: 'iPad',
                version: 'OS'
            },
            {
                name: 'Kindle',
                value: 'Silk',
                version: 'Silk'
            },
            {
                name: 'Android',
                value: 'Android',
                version: 'Android'
            },
            {
                name: 'PlayBook',
                value: 'PlayBook',
                version: 'OS'
            },
            {
                name: 'BlackBerry',
                value: 'BlackBerry',
                version: '/'
            },
            {
                name: 'Macintosh',
                value: 'Mac',
                version: 'OS X'
            },
            {
                name: 'Linux',
                value: 'Linux',
                version: 'rv'
            },
            {
                name: 'Palm',
                value: 'Palm',
                version: 'PalmOS'
            }
        ],
        databrowser: [{
                name: 'Chrome',
                value: 'Chrome',
                version: 'Chrome'
            },
            {
                name: 'Firefox',
                value: 'Firefox',
                version: 'Firefox'
            },
            {
                name: 'Safari',
                value: 'Safari',
                version: 'Version'
            },
            {
                name: 'Internet Explorer',
                value: 'MSIE',
                version: 'MSIE'
            },
            {
                name: 'Opera',
                value: 'Opera',
                version: 'Opera'
            },
            {
                name: 'BlackBerry',
                value: 'CLDC',
                version: 'CLDC'
            },
            {
                name: 'Mozilla',
                value: 'Mozilla',
                version: 'Mozilla'
            }
        ],
        init: function() {
            var agent = this.header.join(' '),
                os = this.matchItem(agent, this.dataos),
                browser = this.matchItem(agent, this.databrowser);

            return {
                os: os,
                browser: browser
            };
        },
        matchItem: function(string, data) {
            var i = 0,
                j = 0,
                html = '',
                regex,
                regexv,
                match,
                matches,
                version;

            for (i = 0; i < data.length; i += 1) {
                regex = new RegExp(data[i].value, 'i');
                match = regex.test(string);
                if (match) {
                    regexv = new RegExp(data[i].version + '[- /:;]([\\d._]+)', 'i');
                    matches = string.match(regexv);
                    version = '';
                    if (matches) {
                        if (matches[1]) {
                            matches = matches[1];
                        }
                    }
                    if (matches) {
                        matches = matches.split(/[._]+/);
                        for (j = 0; j < matches.length; j += 1) {
                            if (j === 0) {
                                version += matches[j] + '.';
                            } else {
                                version += matches[j];
                            }
                        }
                    } else {
                        version = '0';
                    }
                    return {
                        name: data[i].name,
                        version: parseFloat(version)
                    };
                }
            }
            return {
                name: 'unknown',
                version: 0
            };
        }
    };
    var e = module.init();
    console.log(e);

    info = {
        osName: e.os.name,
        osVersion: e.os.version,
        browserName: e.browser.name,
        browserVersion: e.browser.version,
        userAgent: navigator.userAgent,
        appVersion: navigator.appVersion,
        platform: navigator.platform,
        vendor: navigator.vendor
    };
    return info;
};

// document.getElementById("signIn").onclick = login();
var info = getDeviceInfo();
var debug = '';
debug += 'os.name = ' + info.osName + '<br/>';
debug += 'os.version = ' + info.osVersion + '<br/>';
debug += 'browser.name = ' + info.browserName + '<br/>';
debug += 'browser.version = ' + info.browserVersion + '<br/>';

debug += '<br/>';
debug += 'navigator.userAgent = ' + info.userAgent + '<br/>';
debug += 'navigator.appVersion = ' + info.appVersion + '<br/>';
debug += 'navigator.platform = ' + info.platform + '<br/>';
debug += 'navigator.vendor = ' + info.vendor + '<br/>';

// debug += 'web url = ' + info.href + '<br/>';
// document.getElementById('log').innerHTML = JSON.stringify(info);;