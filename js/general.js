function compile_json()
{
	var obj = {};
	for(i=0; i<arguments.length; i++)
	{
		var temp = $("#" + arguments[i]).val();
		temp = temp.replace("&", "~~~");
		eval("obj." + arguments[i] + " = '" + addslashes(temp) + "';");
	}
	
	var json_string = $.json.encode(obj);
	return json_string;
}

function clear_fields()
{
	for(i=0; i<arguments.length; i++)
	{
		$("#" + arguments[i]).val('');
	}
}

function populate_template(obj, template)
{
	var value = "";
	for (var name in obj) 
	{
		if(typeof(highlight_fields) != "undefined")
		{
			if(highlight_fields[name] != undefined)
			{
				intIndexOfMatch = obj[name].indexOf("|hl_");
				while (intIndexOfMatch != -1)
				{
					obj[name] = obj[name].replace("|hl_", "<span style='background: ");
					obj[name] = obj[name].replace("_|", ";'>");
					obj[name] = obj[name].replace("|hl|", "</span>");
					
					intIndexOfMatch = obj[name].indexOf("|hl_");
				}
			}
		}
		
		sRegExInput = new RegExp("{" + name + "}", "g");
		template = template.replace(sRegExInput, obj[name]);
	}
	
	return template;
}

function order_by(action, order_field, container, template, dir)
{
	eval("var stored_values = " + action + ";");
	if(order_field == stored_values['order'])
	{
		if(dir)
		{
			eval(action + "['dir'] = '" + dir + "';");
		}
		else
		{
			if(stored_values['dir'] == "desc")
			{
				eval(action + "['dir'] = 'asc';");
			}
			else
			{
				eval(action + "['dir'] = 'desc';");
			}
		}
	}
	else
	{
		eval(action + "['order'] = order_field;");
		eval(action + "['dir'] = 'desc';");
	}
	
	flip_page(action, 0, 0, container, template);
}

function flip_page(action, page, records, container, template, search)
{
	eval("var stored_values = " + action + ";");
	if(page == 0)
	{
		page = stored_values['start'];
	}
	else
	{
		eval(action + "['start'] = page;");
	}
	if(records == 0)
	{
		records = stored_values['records'];
	}
	else
	{
		eval(action + "['records'] = records;");
	}	
	
	var obj = {};
	
	if(search != undefined)
	{
		eval(action + "['search'] = search;");
		obj['search'] = search;
	}
	
	obj['start'] = page;
	obj['records'] = records;
	for(var name in stored_values)
	{
		if(obj[name] == undefined)
		{
			obj[name] = urlencode(stored_values[name]);
		}
	}
	
	//obj['order'] = stored_values['order'];
	//obj['dir'] = stored_values['dir'];
	
	var json_string = $.json.encode(obj);
	
	$.ajax({
		type: "GET",
		url: "ajax_loader.php?action=" + action,
		cache: false,
		data: "json_string=" + json_string,
		success: function(data)
		{
			var obj = $.json.decode(data);
			eval("var sales_temp = " + template + "_template;");
			
			if(typeof stored_values['adgroup'] == "undefined")
			{
				$('#' + container).html('');
			}
			else
			{
				$('#adgroup_' + stored_values['adgroup'].replace(" ", "") + '_expand').html("<a class=\"smalllink\" href=\"#\" onclick=\"contract_adgroup_keywords('" + stored_values['adgroup'] + "'); return false;\">Contract</a>");
			}
			
			for(i=0; i<obj['results'].length; i++)
			{
				if(typeof stored_values['adgroup'] != "undefined")
				{
					$('#' + container).after(populate_template(obj['results'][i], sales_temp));
					if(typeof expand_list[stored_values['adgroup'].replace(" ", "")] == "undefined")
					{
						expand_list[stored_values['adgroup'].replace(" ", "")] = new Array();
					}
					
					expand_list[stored_values['adgroup'].replace(" ", "")][obj['results'][i]['keyword_id']] = 1;
				}
				else
				{
					$('#' + container).append(populate_template(obj['results'][i], sales_temp));
				}
			}
			
			if(typeof obj['pagination'] != "undefined")
			{
				$('#pagination_top').html(obj['pagination']);
				$('#pagination_bottom').html(obj['pagination']);
			}
			
			if((typeof network_change != "undefined") && (network_change == true))
			{
				network_change = false;
				if(typeof obj['merchant_list'] != "undefined")
				{
					$('#merchant_search_list').empty();
					$('#merchant_search_list').append('<option>All</option>');
					for(i=0; i<obj['merchant_list'].length; i++)
					{
						$('#merchant_search_list').append('<option value="' + obj['merchant_list'][i][0] + '">' + obj['merchant_list'][i][1] + '</option>');
					}
				}
			}
			delete keywords_page['adgroup'];
		}
	});
}

function populate_general_error_box(errors)
{
	var error_list_compile = "<ul>";
	for(i=0; i<errors.length; i++)
	{
		error_list_compile += "<li>" + errors[i] + "</li>";
	}
	error_list_compile += "</ul>";
	$('#error_box_errors').html(error_list_compile);
	
	$('#error_box').css('left', ((document.body.clientWidth / 2) - 250));
	$('#error_box').css('top', (180 + scroll_postion()));
	$('#error_box').show();
}

function close_error_box()
{
	$('#error_box').hide();
	$('#error_box_errors').html('');
}

function scroll_postion()
{
	var ScrollTop = document.body.scrollTop;

	if (ScrollTop == 0)
	{
		if (window.pageYOffset)
		{
			ScrollTop = window.pageYOffset;
		}
		else
		{
			ScrollTop = (document.body.parentElement) ? document.body.parentElement.scrollTop : 0;
		}
	}
	
	return ScrollTop;
}

function addslashes (str) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Ates Goral (http://magnetiq.com)
    // +   improved by: marrtins
    // +   improved by: Nate
    // +   improved by: Onno Marsman
    // +   input by: Denny Wardhana
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: addslashes("kevin's birthday");
    // *     returns 1: 'kevin\'s birthday'
 
    return (str+'').replace(/([\\"'])/g, "\\$1").replace(/\u0000/g, "\\0");
}

function urlencode (str) {
    // http://kevin.vanzonneveld.net
    // +   original by: Philip Peterson
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: AJ
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: travc
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Lars Fischer
    // +      input by: Ratheous
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Joris
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // %          note 1: This reflects PHP 5.3/6.0+ behavior
    // %        note 2: Please be aware that this function expects to encode into UTF-8 encoded strings, as found on
    // %        note 2: pages served as UTF-8
    // *     example 1: urlencode('Kevin van Zonneveld!');
    // *     returns 1: 'Kevin+van+Zonneveld%21'
    // *     example 2: urlencode('http://kevin.vanzonneveld.net/');
    // *     returns 2: 'http%3A%2F%2Fkevin.vanzonneveld.net%2F'
    // *     example 3: urlencode('http://www.google.nl/search?q=php.js&ie=utf-8&oe=utf-8&aq=t&rls=com.ubuntu:en-US:unofficial&client=firefox-a');
    // *     returns 3: 'http%3A%2F%2Fwww.google.nl%2Fsearch%3Fq%3Dphp.js%26ie%3Dutf-8%26oe%3Dutf-8%26aq%3Dt%26rls%3Dcom.ubuntu%3Aen-US%3Aunofficial%26client%3Dfirefox-a'

    str = (str+'').toString();
    
    // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
                                                                    replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
}

function date(format, timestamp) {
    // http://kevin.vanzonneveld.net
    // +   original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
    // +      parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: MeEtc (http://yass.meetcweb.com)
    // +   improved by: Brad Touesnard
    // +   improved by: Tim Wiel
    // +   improved by: Bryan Elliott
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: David Randall
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +  derived from: gettimeofday
    // +      input by: majak
    // +   bugfixed by: majak
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: Alex
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Theriault
    // +   improved by: Thomas Beaucourt (http://www.webapp.fr)
    // +   improved by: JT
    // +   improved by: Theriault
    // +   improved by: Rafal Kukawski (http://blog.kukawski.pl)
    // %        note 1: Uses global: php_js to store the default timezone
    // *     example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);
    // *     returns 1: '09:09:40 m is month'
    // *     example 2: date('F j, Y, g:i a', 1062462400);
    // *     returns 2: 'September 2, 2003, 2:26 am'
    // *     example 3: date('Y W o', 1062462400);
    // *     returns 3: '2003 36 2003'
    // *     example 4: x = date('Y m d', (new Date()).getTime()/1000); 
    // *     example 4: (x+'').length == 10 // 2009 01 09
    // *     returns 4: true
    // *     example 5: date('W', 1104534000);
    // *     returns 5: '53'
    // *     example 6: date('B t', 1104534000);
    // *     returns 6: '999 31'
    // *     example 7: date('W U', 1293750000.82); // 2010-12-31
    // *     returns 7: '52 1293750000'
    // *     example 8: date('W', 1293836400); // 2011-01-01
    // *     returns 8: '52'
    // *     example 9: date('W Y-m-d', 1293974054); // 2011-01-02
    // *     returns 9: '52 2011-01-02'
    var that = this,
        jsdate, f, formatChr = /\\?([a-z])/gi, formatChrCb,
        // Keep this here (works, but for code commented-out
        // below for file size reasons)
        //, tal= [],
        _pad = function (n, c) {
            if ((n = n + "").length < c) {
                return new Array((++c) - n.length).join("0") + n;
            } else {
                return n;
            }
        },
        txt_words = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur",
        "January", "February", "March", "April", "May", "June", "July",
        "August", "September", "October", "November", "December"],
        txt_ordin = {
            1: "st",
            2: "nd",
            3: "rd",
            21: "st", 
            22: "nd",
            23: "rd",
            31: "st"
        };
    formatChrCb = function (t, s) {
        return f[t] ? f[t]() : s;
    };
    f = {
    // Day
        d: function () { // Day of month w/leading 0; 01..31
            return _pad(f.j(), 2);
        },
        D: function () { // Shorthand day name; Mon...Sun
            return f.l().slice(0, 3);
        },
        j: function () { // Day of month; 1..31
            return jsdate.getDate();
        },
        l: function () { // Full day name; Monday...Sunday
            return txt_words[f.w()] + 'day';
        },
        N: function () { // ISO-8601 day of week; 1[Mon]..7[Sun]
            return f.w() || 7;
        },
        S: function () { // Ordinal suffix for day of month; st, nd, rd, th
            return txt_ordin[f.j()] || 'th';
        },
        w: function () { // Day of week; 0[Sun]..6[Sat]
            return jsdate.getDay();
        },
        z: function () { // Day of year; 0..365
            var a = new Date(f.Y(), f.n() - 1, f.j()),
                b = new Date(f.Y(), 0, 1);
            return Math.round((a - b) / 864e5) + 1;
        },

    // Week
        W: function () { // ISO-8601 week number
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3),
                b = new Date(a.getFullYear(), 0, 4);
            return 1 + Math.round((a - b) / 864e5 / 7);
        },

    // Month
        F: function () { // Full month name; January...December
            return txt_words[6 + f.n()];
        },
        m: function () { // Month w/leading 0; 01...12
            return _pad(f.n(), 2);
        },
        M: function () { // Shorthand month name; Jan...Dec
            return f.F().slice(0, 3);
        },
        n: function () { // Month; 1...12
            return jsdate.getMonth() + 1;
        },
        t: function () { // Days in month; 28...31
            return (new Date(f.Y(), f.n(), 0)).getDate();
        },

    // Year
        L: function () { // Is leap year?; 0 or 1
            var y = f.Y(), a = y & 3, b = y % 4e2, c = y % 1e2;
            return 0 + (!a && (c || !b));
        },
        o: function () { // ISO-8601 year
            var n = f.n(), W = f.W(), Y = f.Y();
            return Y + (n === 12 && W < 9 ? -1 : n === 1 && W > 9);
        },
        Y: function () { // Full year; e.g. 1980...2010
            return jsdate.getFullYear();
        },
        y: function () { // Last two digits of year; 00...99
            return (f.Y() + "").slice(-2);
        },

    // Time
        a: function () { // am or pm
            return jsdate.getHours() > 11 ? "pm" : "am";
        },
        A: function () { // AM or PM
            return f.a().toUpperCase();
        },
        B: function () { // Swatch Internet time; 000..999
            var H = jsdate.getUTCHours() * 36e2, // Hours
                i = jsdate.getUTCMinutes() * 60, // Minutes
                s = jsdate.getUTCSeconds(); // Seconds
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
        },
        g: function () { // 12-Hours; 1..12
            return f.G() % 12 || 12;
        },
        G: function () { // 24-Hours; 0..23
            return jsdate.getHours();
        },
        h: function () { // 12-Hours w/leading 0; 01..12
            return _pad(f.g(), 2);
        },
        H: function () { // 24-Hours w/leading 0; 00..23
            return _pad(f.G(), 2);
        },
        i: function () { // Minutes w/leading 0; 00..59
            return _pad(jsdate.getMinutes(), 2);
        },
        s: function () { // Seconds w/leading 0; 00..59
            return _pad(jsdate.getSeconds(), 2);
        },
        u: function () { // Microseconds; 000000-999000
            return _pad(jsdate.getMilliseconds() * 1000, 6);
        },

    // Timezone
        e: function () { // Timezone identifier; e.g. Atlantic/Azores, ...
// The following works, but requires inclusion of the very large
// timezone_abbreviations_list() function.
/*              var abbr = '', i = 0, os = 0;
            if (that.php_js && that.php_js.default_timezone) {
                return that.php_js.default_timezone;
            }
            if (!tal.length) {
                tal = that.timezone_abbreviations_list();
            }
            for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                    os = -jsdate.getTimezoneOffset() * 60;
                    if (tal[abbr][i].offset === os) {
                        return tal[abbr][i].timezone_id;
                    }
                }
            }
*/
            return 'UTC';
        },
        I: function () { // DST observed?; 0 or 1
            // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
            // If they are not equal, then DST is observed.
            var a = new Date(f.Y(), 0), // Jan 1
                c = Date.UTC(f.Y(), 0), // Jan 1 UTC
                b = new Date(f.Y(), 6), // Jul 1
                d = Date.UTC(f.Y(), 6); // Jul 1 UTC
            return 0 + ((a - c) !== (b - d));
        },
        O: function () { // Difference to GMT in hour format; e.g. +0200
            var a = jsdate.getTimezoneOffset();
            return (a > 0 ? "-" : "+") + _pad(Math.abs(a / 60 * 100), 4);
        },
        P: function () { // Difference to GMT w/colon; e.g. +02:00
            var O = f.O();
            return (O.substr(0, 3) + ":" + O.substr(3, 2));
        },
        T: function () { // Timezone abbreviation; e.g. EST, MDT, ...
// The following works, but requires inclusion of the very
// large timezone_abbreviations_list() function.
/*              var abbr = '', i = 0, os = 0, default = 0;
            if (!tal.length) {
                tal = that.timezone_abbreviations_list();
            }
            if (that.php_js && that.php_js.default_timezone) {
                default = that.php_js.default_timezone;
                for (abbr in tal) {
                    for (i=0; i < tal[abbr].length; i++) {
                        if (tal[abbr][i].timezone_id === default) {
                            return abbr.toUpperCase();
                        }
                    }
                }
            }
            for (abbr in tal) {
                for (i = 0; i < tal[abbr].length; i++) {
                    os = -jsdate.getTimezoneOffset() * 60;
                    if (tal[abbr][i].offset === os) {
                        return abbr.toUpperCase();
                    }
                }
            }
*/
            return 'UTC';
        },
        Z: function () { // Timezone offset in seconds (-43200...50400)
            return -jsdate.getTimezoneOffset() * 60;
        },

    // Full Date/Time
        c: function () { // ISO-8601 date.
            return 'Y-m-d\\Th:i:sP'.replace(formatChr, formatChrCb);
        },
        r: function () { // RFC 2822
            return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
        },
        U: function () { // Seconds since UNIX epoch
            return jsdate.getTime() / 1000 | 0;
        }
    };
    this.date = function (format, timestamp) {
        that = this;
        jsdate = (
            (typeof timestamp === 'undefined') ? new Date() : // Not provided
            (timestamp instanceof Date) ? new Date(timestamp) : // JS Date()
            new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
        );
        return format.replace(formatChr, formatChrCb);
    };
    return this.date(format, timestamp);
}