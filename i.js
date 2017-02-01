'use strict';

/*
 * global scope variables
 */
var AMAZON = 'https://d3ibvo9wps41sr.cloudfront.net/';

var notification1 = 0; // general
var notification2 = 0; // chat
var info1 = 0; // 1 true, returns user/post info along with the requested data
var page1 = 0; // page number of the results (100 results per page)
var type1 = 0; // 0 all posts vs 2 photos
var page2 = 0; // page number of the results (100 results per page)
var type2 = 0; // 0 all vs 1 read threads
// uid, pid, tab

/*
 * @param {String} url The request URL
 * @param {String} parameters The request parameters
 */
function request(url, parameters) {
    return new Promise(function(resolve, reject) {
        let request = new XMLHttpRequest();
        request.open('POST', url, true);
        request.setRequestHeader('Content-type',
          'application/x-www-form-urlencoded');
        request.onload = function() {
            if (request.status == 200) {
                resolve(request.responseText.split("\n"));
            } else {
                reject(Error(request.statusText));
            }
        };
        request.onerror = function() {
            reject(Error('Error'));
        };
        request.send(parameters);
  });
}

/*
 * @param {Integer} no Tab to select
 */
function selectTab(no) {
    tab = no;
    for (var i = 1; i != 4; ++i) {
        let obj = document.getElementById('tab' + i);
        if (obj !== null) {
            obj.style.background = (i == no ? '#DDD' : 'white');
        }
    }
}

/*
 * @param {String} str Text to be marked
 * @param {Integer} type Text or image with text post
 */
function markHashtags(str, type) {
    var tag = false;
    var result = '';
    for (var i = 0; i != str.length; ++i) {
        if (str[i] == '#') {
            if (tag == false) {
                tag = true;
                if (type == 1) {
                    result += '<span style="color: #4060A0; text-shadow: 0 0 4px #8FF;">';
                } else {
                    result += '<span style="color: #FFA; text-shadow: 0 0 4px #000;">';
                }
            }
        } else if (str[i] == ' ') {
            if (tag == true) {
                tag = false;
                result += '</span>';
            }
            result += ' ';
        } else {
            result += str[i];
        }
    }
    if (tag == true)
    result += '</font>';
    return result;
}

/*
 * @param {String} str Text to be safely escaped using the browser's built-in
 *   functionality
 */
function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/*
 * @param {Integer} epoch Number of seconds to convert from Unix epoch to user
 *   readable timestamp
 */
function epochToTimestamp(epoch) {
    var sec = Date.now() / 1000 - epoch;
    var time = '';
    if (sec < 3600) {
        time += parseInt(sec / 60) + 'm';
    } else if (sec < 86400) {
        time += parseInt(sec / 3600) + 'h';
    } else if (sec < 2592000) {
        time += parseInt(sec / 86400) + 'd';
    } else if (sec < 31557600) {
        time += parseInt(sec / 2592000) + 'M';
    } else {
        time += parseInt(sec / 31557600) + 'Y';
    }
    return time;
}

/*
 * show notification alerts
 */
function showNotifications() {
    document.getElementById('notification1').style.display =
      (notification1 == 0 ? 'none' : 'block');
    document.getElementById('notification2').style.display =
      (notification2 == 0 ? 'none' : 'block');
}

/*
 * @param {String} message Error message to display
 */
function setError(message) {
    let error = document.getElementById('error');
    error.innerHTML = message;
    error.style.display = 'block';
}

/*
 * @param {Boolean} state State of the preview
 */
function setPreview(state) {
    document.getElementById('preview').innerHTML =
      (state ? '<img style="height: 44px" src="' + AMAZON + 'res/ok.png" />' : '*optional');
}

/*
 * Displays posts page
 */
function postsPage() {
    // layout
    document.getElementsByTagName('body')[0].insertAdjacentHTML('beforeend',
      '<img id="notification1" class="notification1" src="' + AMAZON + 'res/notification1.png" alt="" onclick="this.style.display = \'none\'; location.href = \'posts.php?uid=' + _UID + '&tab=3\';" />' +
      '<img id="notification2" class="notification2" src="' + AMAZON + 'res/notification2.png" alt="" onclick="this.style.display = \'none\'; Android.loadUrl(\'https://findastranger.com/threads.php\', 2)" />' +
      notificationEntry() +
      '<img id="user" class="userimage" src="' + AMAZON + 'u/' + (uid ? uid : _UID) + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" alt="" onclick="location.href = \'posts.php?uid=' + (uid ? uid : _UID) + '\'" />' +
      '<div id="header" style="min-height: 100px">' +
      (pid ? '<div style="height: 100px; padding-top: 10px;"></div>' : '') +
      '</div>' +
      '<div id="menu"></div>' +
      '<div id="list"></div>' +
      '<img id="loading" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" />' +
      '<img id="more" class="more" src="' + AMAZON + 'res/more1.png" alt="" />');

    // menu
    if (pid) {
        // posts related to a post(uid,pid)
        document.getElementById('menu').insertAdjacentHTML('beforeend',
          '&nbsp;<br />' +
          '<div style="width: 33.33%; float: left;"><div id="tab1" class="tab" style="margin: 0 3px 0 6px;" onclick="info1 = 0; page1 = 0; type1 = 0; getPosts();">Related</div></div>' +
          '<div style="width: 33.33%; float: left;"><div id="tab2" class="tab" style="margin: 0 3px 0 3px;" onclick="info1 = 0; page1 = 0; getLikes();">Likes</div></div>' +
          '<div style="width: 33.33%; float: left;"><div id="tab3" class="tab" style="margin: 0 6px 0 3px;" onclick="info1 = 0; page1 = 0; getComments();">Comments</div></div>' +
          '<br />&nbsp;');
    } else if (uid) {
        // posts of user(uid)
        document.getElementById('menu').insertAdjacentHTML('beforeend',
          '&nbsp;<br />' +
          '<div style="width: 33.33%; float: left;"><div id="tab1" class="tab" style="margin: 0 3px 0 6px;" onclick="info1 = 0; page1 = 0; type1 = 0; getPosts();">Posts</div></div>' +
          '<div style="width: 33.33%; float: left;"><div id="tab2" class="tab" style="margin: 0 3px 0 3px;" onclick="info1 = 0; page1 = 0; type1 = 2; getPosts();">Photos</div></div>' +
          '<div style="width: 33.33%; float: left;"><div id="tab3" class="tab" style="margin: 0 6px 0 3px;" onclick="info1 = 0; page1 = 0; getFeed();">' + (uid == _UID ? 'Feed' : 'Interactions') + '</div></div>' +
          '<br />&nbsp;');
    }

    // list
    if (!pid) {
        if (tab == 0 || tab == 1) {
            info1 = uid ? 1 : 0; page1 = 0; type1 = 0; getPosts();
        } else if (tab == 2) {
            info1 = 1; page1 = 0; type1 = 2; getPosts();
        } else if (tab == 3) {
            info1 = 1; page1 = 0; getFeed();
        }
    } else {
        if (tab == 0 || tab == 1) {
            info1 = 1; page1 = 0; type1 = 0; getPosts();
        } else if (tab == 2) {
            info1 = 1; page1 = 0; getLikes();
        } else if (tab == 3) {
            info1 = 1; page1 = 0; getComments();
        }
    }
}

/*
 * Displays threads page
 */
function threadsPage() {
    // layout
    document.getElementsByTagName('body')[0].insertAdjacentHTML('beforeend',
      '<div class="title">' +
      '<div class="button" onclick="location.href = \'threads.php?type=' + type2 + '\'">⟳</div>' +
      '<div class="button" onclick="location.href = \'threads.php?type=' + (type2 == 0 ? '1' : '0') + '\'">' + (type2 == 0 ? 'All' : 'Unread') + ' Messages</div>' +
      '</div><p>' +
      '<div id="list" style="margin-bottom: 100px"></div>' +
      '<img id="loading" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" />' +
      '<img id="more" class="more" src="' + AMAZON + 'res/more1.png" alt="" />');

    // list
    page2 = 0; getThreads();
}

/*
 * Displays messages page
 */
function messagesPage() {
    // layout
    document.getElementsByTagName('body')[0].insertAdjacentHTML('beforeend',
      '<div class="title" style="padding-right: 85px;">' +
      '<div class="button" onclick="location.href = \'threads.php\'"><img class="menu4" src="' + AMAZON + 'res/left.png" />Inbox</div>' +
      '<div class="button" onclick="deleteThread()">Clear</div>' +
      '</div><p>' +
      '<img id="notification" class="notification" src="" alt="" onclick="Android.notifications()" />' +
      '<img id="user" class="userimage" src="' + AMAZON + 'u/' + uid + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" alt="" onclick="Android.loadUrl(\'https://findastranger.com/posts.php?uid=' + uid + '\', 1)" />' +
      '<img id="more" class="more" src="' + AMAZON + 'res/more2.png" alt="" />' +
      '<img id="loading" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" />' +
      '<div id="list" style="margin-bottom: 100px"></div>' +
      '<div class="newmessage">' +
      '<textarea id="message" placeholder="enter message" tabindex="1" autocomplete="off" maxlength="300" style="height: 100%" onkeyup="if (event.keyCode === 13) {newMessage();} return true;"></textarea>' +
      '<img id="loading2" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" />' +
      '</div>');

    // list
    page2 = 0; getMessages();
}

/*
 * Displays user interactions page
 */
function usersPage() {
    // layout
    document.getElementsByTagName('body')[0].insertAdjacentHTML('beforeend',
      '<img id="notification1" class="notification1" src="' + AMAZON + 'res/notification1.png" alt="" onclick="this.style.display = \'none\'; location.href = \'posts.php?uid=' + _UID + '&tab=3\';" />' +
      '<img id="notification2" class="notification2" src="' + AMAZON + 'res/notification2.png" alt="" onclick="this.style.display = \'none\'; Android.loadUrl(\'https://findastranger.com/threads.php\', 2)" />' +
      '<div class="title"><img class="menu4" src="' + AMAZON +'res/user.png" alt="" />Top User Interactions</div><br />' +
      '<div id="list"></div>' +
      '<img id="loading" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" />' +
      '<img id="more" class="more" src="' + AMAZON + 'res/more1.png" alt="" />');

    // list
    page1 = 0;
    getUsers();
}

/*
 * Displays threads page
 */
function threadsPage() {
    // layout
    document.getElementsByTagName('body')[0].insertAdjacentHTML('beforeend',
      '<div class="title">' +
      '<div class="button" onclick="location.href = \'threads.php?type=' + type2 + '\'">⟳</div>' +
      '<div class="button" onclick="location.href = \'threads.php?type=' + (type2 == 0 ? '1' : '0') + '\'">' + (type2 == 0 ? 'All' : 'Unread') + ' Messages</div>' +
      '</div><p>' +
      '<div id="list" style="margin-bottom: 100px"></div>' +
      '<img id="loading" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" />' +
      '<img id="more" class="more" src="' + AMAZON + 'res/more1.png" alt="" />');

    // list
    page2 = 0; getThreads();
}

/*
 * @param {Array} cols The comment info
 */
function commentEntry(cols) {
    // uid  cid  time  comment
    let html =
      '<div style="cursor: pointer; position: relative;">' +
      '<img class="postuser1" src="' + AMAZON + 'u/' + cols[0] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" onclick="location.href=\'posts.php?uid=' + cols[0] + '\'" />' +
      '<div class="posttext1">' + escapeHtml(cols[3]) +
      ' <span class="time">&nbsp;' + epochToTimestamp(cols[2]) + '&nbsp;</span>' +
      ((_UID == uid || _UID == cols[0]) ? ' <span class="delete" onclick="deleteComment(' + cols[1] + ')">&nbsp;x&nbsp;</span>' : '') +
      '</div>' +
      '<div style="clear: both"></div></div>';
    return html;
}

/*
 * @param {Array} cols The feed info
 */
function feedEntry(cols) {
    // time _uid uid pid cid type post comment
    let html =
      '<div style="cursor: pointer; position: relative;" onclick="location = \'posts.php?uid=' + cols[2] + '&pid=' + cols[3] + '&tab=' +
      (cols[4] == 0 ? '2' : '3') +
      '\';">' +
      (cols[5] == 2 ? '<div style="float: right; margin: 0 5px 0 5px;"><img src="' + AMAZON + 'p1/' + cols[2] + '_' + cols[3] + '.jpg" style="width: 50px; height: 50px; border-radius: 2px;" /></div>' : '' ) +
      '<img class="postuser1" src="' + AMAZON + 'u/' + cols[1] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" />' +
      '<div class="posttext1">' +
      (cols[4] == 0 ? '<span class="like">♥</span> ' : '<span class="comment">💬</span> ') +
      (cols[4] == 0 ? markHashtags(escapeHtml(cols[6]), 1) : escapeHtml(cols[7])) +
      ' <span class="time">&nbsp;' + epochToTimestamp(cols[0]) + '&nbsp;</span>' +
      '</div>' +
      '</div><div style="clear: both;"></div>';
    return html;
}

/*
 * @param {Array} cols The message info
 */
function messageEntry(cols) {
    // uid time message
    let html =
      '<div style="position: relative;">' +
      '<img class="postuser1" src="' + AMAZON + 'u/' + cols[0] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" />' +
      '<div class="posttext1">' +
      '<span>' + escapeHtml(cols[2]) + '</span>' + // format(..., ...) ??
      ' <span class="time">&nbsp;' + epochToTimestamp(cols[1]) + '&nbsp;</span>' +
      '</div>' +
      '<div style="clear: both"></div></div>';
    return html;
}

/*
 * Displays notification
 */
function notificationEntry() {
    if (notification3 === undefined || notification3.length === 0 ||
      Math.floor(Math.random() * 5) != 2) {
        return '';
    }
    let i = Math.floor(Math.random() * notification3.length);
    let onclick = '';
    let message = '';

    if (notification3[i] == 'state') {
        onclick = 'location.href = \'https://findastranger.com/referral.php\'';
        message = 'Refer a friend to unlock all features';
    } else if (notification3[i] == 'email') {
        onclick = 'location.href = \'https://findastranger.com/change_email.php\'';
        message = 'Add e-mail address for recovery';
    } else if (notification3[i] == 'name') {
        onclick = 'location.href = \'https://findastranger.com/edit_profile.php\'';
        message = 'Enter your name';
    } else if (notification3[i] == 'age') {
        onclick = 'location.href = \'https://findastranger.com/edit_profile.php\'';
        message = 'Select your age';
    } else if (notification3[i] == 'sex') {
        onclick = 'location.href = \'https://findastranger.com/edit_profile.php\'';
        message = 'Specify your gender';
    }

    let html =
      '<div id="notification3" class="notification3" onclick="' + onclick + '">' +
      message +
      '</div>';
    return html;
}

/*
 * @param {Array} cols The post info
 */
function postEntry(cols, header) {
    // uid pid type time distance likes like comments filter post
    let html =
      '<div style="cursor: pointer; position: relative;" onclick="' +
      (header ? 'newLike()' : 'location.href=\'posts.php?uid=' + cols[0] + '&pid=' + cols[1] + '\'') +
      '">';
    if (cols[2] == 2) {
        html +=
          '<img style="width: 100%" src="' + AMAZON + 'p2/' + cols[0] + '_' + cols[1] + '.jpg" />';
    }
    html +=
      '<img class="postuser' + cols[2] + '" src="' + AMAZON + 'u/' + cols[0] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" />' +
      '<div class="posttext' + cols[2] + '">' +
      '<span>' + markHashtags(escapeHtml(cols[9]), cols[2]) + '</span>' +
      (cols[5] > 0 ? ' <span class="likes">&nbsp;' + cols[5] + '&nbsp;</span>' : '') +
      (cols[7] > 0 ? ' <span class="comments">&nbsp;' + cols[7] + '&nbsp;</span>' : '') +
      ' <span class="time">&nbsp;' + epochToTimestamp(cols[3]) + '&nbsp;</span>' +
      (header ? ' <span id="like" class="like">' + (cols[6] == 1 ? '♥' : '') + '</span>' : '') +
      ((header && cols[0] == _UID) ? ' <span id="like" class="delete" onclick="deletePost(' + cols[1] + ')">&nbsp;x&nbsp;</span>' : '') +
      '</div>' +
      '<div style="clear: both"></div></div>';
    return html;
}

/*
 *
 */
function postEntryBlurred() {
    let dictionary = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'I', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at', 'this', 'but', 'his', 'by', 'from', 'they', 'we', 'say', 'her', 'she', 'or', 'an', 'will', 'my', 'one', 'all', 'would', 'there', 'their', 'what', 'so', 'up', 'out', 'if', 'about', 'who', 'get', 'which', 'go', 'me', 'when', 'make', 'can', 'like', 'time', 'no', 'just', 'him', 'know', 'take', 'person', 'into', 'year', 'your', 'good', 'some', 'could', 'them', 'see', 'other', 'than', 'then', 'now', 'look', 'only', 'come', 'its', 'over', 'think', 'also', 'back', 'after', 'use', 'two', 'how', 'our', 'work', 'first', 'well', 'way', 'even', 'new', 'want', 'because', 'any', 'these', 'give', 'day', 'most', 'us'];
    let post = [];
    let words = Math.floor(Math.random() * 30) + 5;
    let tags = Math.floor(Math.random() * 4) + 2;
    for (let i = 0; i != words; ++i) {
        let tag = '';
        if (tags > 0 && Math.floor(Math.random() * words) < tags) {
            tag = '#';
        }
        post[i] = tag + dictionary[Math.floor(Math.random() * dictionary.length)];
    }
    post = post.join(' ');

    let user = Math.floor(Math.random() * 100) + 1;
    let likes = (Math.floor(Math.random() * 2) == 1) ? Math.floor(Math.random() * 50) : 0;
    let comments = (Math.floor(Math.random() * 2) == 1) ? Math.floor(Math.random() * 50) : 0;
    let time = Math.floor(Date.now() / 1000) - Math.floor(Math.random() * 432000);

    let html =
      '<div style="cursor: pointer; position: relative;" onclick="location.href = \'referral.php\'">' +
      '<img class="blurred1" src="' + AMAZON + 'res/' + user + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" />' +
//      '<img class="postuser1" src="' + AMAZON + 'res/' + user + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" />' +
      '<div class="blurred2">' +
      '<span>' + markHashtags(post, 1) + '</span>' +
      (likes > 0 ? ' <span class="likes">&nbsp;' + likes + '&nbsp;</span>' : '') +
      (comments > 0 ? ' <span class="comments">&nbsp;' + comments + '&nbsp;</span>' : '') +
      ' <span class="time">&nbsp;' + epochToTimestamp(time) + '&nbsp;</span>' +
      '</div>' +
      '<div style="clear: both"></div></div>';
    return html;
}

/*
 * @param {Array} cols The thread info
 */
function threadEntry(cols) {
    // uid read time snippet
    let html =
      '<div style="cursor: pointer; position: relative;" onclick="location.href = \'messages.php?uid=' + cols[0] + '\'">' +
      '<img class="postuser1" src="' + AMAZON + 'u/' + cols[0] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" />' +
      '<div class="posttext1"' +
      (cols[1] == 0 ? ' style="font-weight: bold"' : '') +
      '>' +
      '<span>' + escapeHtml(cols[3]) + '</span>' + // format(..., ...) ??
      ' <span class="time">&nbsp;' + epochToTimestamp(cols[2]) + '&nbsp;</span>' +
      '</div>' +
      '<div style="clear: both"></div></div>';
    return html;
}

/*
 * @param {Array} cols The user info
 */
function userEntry(cols) {
    // username name age sex
    let html =
      '<div style="margin-right: 95px; padding: 5px;" onclick="Android.loadUrl(\'https://findastranger.com/messages.php?uid=' + uid + '\', 2)">' +
      '<b>' + escapeHtml(cols[0]) + '</b>' +
      (cols[1] != '' ? '<br />' + escapeHtml(cols[1]) : '') +
      (cols[2] > 0 ? '<br />' + cols[2] : '') +
      (cols[3] == 1 ? ' m' : (cols[3] == 2 ? ' f' : '')) +
      '</div>' +
      '<div class="circle" style="right: 100px" onclick="Android.loadUrl(\'https://findastranger.com/messages.php?uid=' + uid + '\', 2)">⌨</div>';
    return html;
}

/*
 * New comment
 */
function newComment() {
    let comment = document.getElementById('comment');
    let loading = document.getElementById('loading2');
    if (comment.value.length === 0) {
        return;
    }
    comment.style.display = 'none';
    loading.style.display = 'block';
    request('/api/new_comment.php', 'uid=' + uid + '&pid=' + pid + '&comment=' +
      comment.value).then(
        function(response) {
            info1 = 0; page1 = 0; getComments();
        },
        function(error) {
	    comment.style.display = 'block';
            loading.style.display = 'none';
        }
    );
}

/*
 * New like
 */
function newLike() {
    let like = document.getElementById('like');
    like.innerHTML = '<img src="' + AMAZON + 'res/loading.gif" />';
    request('/api/new_like.php', 'uid=' + uid + '&pid=' + pid).then(
        function(response) {
            if (response[0] != 'ok') {
                like.innerHTML = 'error';
                return;
            }
            // page uid pid likes liked
            let header = response[1].split('\t');
            like.innerHTML = (header[4] == 1 ? '♥' : '');
        },
        function(error) {
            like.innerHTML = 'error';
        }
    );
}

/*
 * New message
 */
function newMessage() {
    let message = document.getElementById('message');
    let loading = document.getElementById('loading2');
    if (message.value.length === 0) {
        return;
    }
    message.style.display = 'none';
    loading.style.display = 'block';
    request('/api/new_message.php', 'uid=' + uid + '&message=' +
      message.value).then(
        function(response) {
            message.value = '';
	    message.style.display = 'block';
            page2 = 0; getMessages();
        },
        function(error) {
	    message.style.display = 'block';
            loading.style.display = 'none';
        }
    );
}

/*
 * Delete comment
 */
function deleteComment(cid) {
    request('/api/delete_comment.php', 'uid=' + uid + '&pid=' + pid + '&cid=' +
      cid).then(
        function(response) {
            info1 = 0; page1 = 0; getComments();
        },
        function(error) {}
    );
}

/*
 * Delete post
 */
function deletePost(pid) {
    request('/api/delete_post.php', 'uid=' + uid + '&pid=' + pid).then(
        function(response) {
            location.href = 'posts.php?uid=' + _UID;
        },
        function(error) {}
    );
}

/*
 * Delete thread
 */
function deleteThread() {
    request('/api/delete_thread.php', 'uid=' + uid).then(
        function(response) {
            location.href = 'threads.php';
        },
        function(error) {}
    );
}

/*
 * Get comments
 */
function getComments() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page1 == 0) {
        selectTab(3);
        list.innerHTML =
          '<div style="margin: 0 5px 0 5px"><textarea id="comment" placeholder="new comment" tabindex="1" autocomplete="off" maxlength="300" rows="3" onkeyup="if (event.keyCode === 13) {newComment();} return true;"></textarea></div>' +
          '<img id="loading2" class="loading" src="' + AMAZON + 'res/loading.gif" alt="" /><p />';
        more.onclick = function () {getComments();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_comments.php', 'uid=' + uid + '&pid=' + pid +
      '&info=' + info1 + '&page=' + page1).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                return;
            }
            // page uid pid
            let header = response[1].split('\t');
            let cols = response[2].split('\t');
            notification1 = cols[0];
            notification2 = cols[1];
            showNotifications();
            cols =  response[3].split('\t');
            if (info1) {
                document.getElementById('header').insertAdjacentHTML(
                  'beforeend', postEntry(cols));
            }
            for (let i = 4; i != response.length; ++i) {
                let cols = response[i].split('\t');
                if (page1 != 0 || i != 4) {
                    list.insertAdjacentHTML('beforeend', '<hr />');
                }
                list.insertAdjacentHTML('beforeend', commentEntry(cols));
            }
            page1 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 4 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
        }
    );
}

/*
 * Get feed
 */
function getFeed() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page1 == 0) {
        selectTab(3);
        list.innerHTML = '';
        more.onclick = function () {getFeed();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_feed.php', 'uid=' + uid + 
      '&info=' + info1 + '&page=' + page1).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                return;
            }
            // page uid
            let header = response[1].split('\t');
            let cols = response[2].split('\t');
            notification1 = cols[0];
            notification2 = cols[1];
            showNotifications();
            cols =  response[3].split('\t');
            if (info1) {
                document.getElementById('header').insertAdjacentHTML(
                  'beforeend', userEntry(cols));
            }
            for (let i = 4; i != response.length; ++i) {
                let cols = response[i].split('\t');
                if (page1 != 0 || i != 4) {
                    list.insertAdjacentHTML('beforeend', '<hr />');
                }
                list.insertAdjacentHTML('beforeend', feedEntry(cols));
            }
            page1 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 4 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
        }
    );
}

/*
 * Get likes
 */
function getLikes() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page1 == 0) {
        selectTab(2);
        list.innerHTML = '';
        more.onclick = function () {getLikes();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_likes.php', 'uid=' + uid + '&pid=' + pid +
      '&info=' + info1 + '&page=' + page1).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                return;
            }
            // page uid pid
            let header = response[1].split('\t');
            let cols = response[2].split('\t');
            notification1 = cols[0];
            notification2 = cols[1];
            showNotifications();
            cols =  response[3].split('\t');
            if (info1) {
                document.getElementById('header').insertAdjacentHTML(
                  'beforeend', postEntry(cols));
            }
            for (let i = 4; i != response.length; ++i) {
                list.insertAdjacentHTML('beforeend',
                  '<a href="posts.php?uid=' + response[i] + '">' +
                  '<img class="likeuser" src="' + AMAZON + 'u/' + response[i] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" /> ' +
                  '</a>');
            }
            page1 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 4 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
        }
    );
}

/*
 * Get messages
 */
function getMessages() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page2 == 0) {
        list.innerHTML = '';
        more.onclick = function () {getMessages();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_messages.php', 'uid=' + uid + '&page=' + page2).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                window.history.back();
                return;
            }
            // page uid
            let header = response[1].split('\t');
            let html = '';
            for (let i = 2; i != response.length; ++i) {
                // uid read time message
                let cols = response[i].split('\t');
                if (header[0] != 0 || i != 2) {
                    html += '<hr />';
                }
                html += messageEntry(cols) + list.innerHTML;
            }
            list.insertAdjacentHTML('afterbegin', html);
            if (page2 == 0) {
                window.scrollTo(0, document.body.scrollHeight);
            }
            page2 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 4 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
            window.history.back();
            return;
        }
    );
}

/*
 * Get posts
 */
function getPosts() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page1 == 0) {
        selectTab((type1 == 0) ? 1 : 2);
        list.innerHTML = '';
        more.onclick = function () {getPosts();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_posts.php', 'uid=' + uid + '&pid=' + pid +
      '&info=' + info1 + '&page=' + page1 + '&type=' + type1).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                window.history.back();
                return;
            }
            // page uid pid
            let header = response[1].split('\t');
            let cols = response[2].split('\t');
            notification1 = cols[0];
            notification2 = cols[1];
            showNotifications();
            cols =  response[3].split('\t');
            if (info1) {
                if (pid) {
                    document.getElementById('header').insertAdjacentHTML(
                      'beforeend', postEntry(cols, 1));
                } else {
                    document.getElementById('header').insertAdjacentHTML(
                      'beforeend', userEntry(cols));
                }
            }
            let prev = 0;
            for (let i = 4; i != response.length; ++i) {
                // uid pid type time distance likes liked comments filter post
                let cols = response[i].split('\t');
                if (prev == 1) {
                  if (cols[2] == 1) {
                      list.insertAdjacentHTML('beforeend', '<hr />');
                  } else {
                      list.insertAdjacentHTML('beforeend',
                        '<div style="height: 10px"></div>');
                  }
                }
                prev = cols[2];
                list.insertAdjacentHTML('beforeend', postEntry(cols, 0));
            }
            if (_STATE == 2 && (uid != _UID || pid != 0) && page1 == 0 &&
              response.length - 4 < 100) {
                // blurred posts
                if (prev == 1) {
                    list.insertAdjacentHTML('beforeend', '<hr />');
                }
                list.insertAdjacentHTML('beforeend', '<center><button class="otherbutton" style="align: center; display: table-cell; text-align: center" onclick="location.href = \'referral.php\'">unblur more related posts</button></center>');
                let n = Math.floor(Math.random() * 20) + 1;
                for (let i = 0; i != n; ++i) {
                    list.insertAdjacentHTML('beforeend', '<hr />' +
                      postEntryBlurred());
                }
            }
            page1 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 4 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
        }
    );
}

/*
 * Get threads
 */
function getThreads() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page2 == 0) {
        list.innerHTML = '';
        more.onclick = function () {getThreads();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_threads.php', 'type=' + type2 + '&page=' + page2).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                return;
            }
            // page
            let header = response[1].split('\t');
            for (let i = 2; i != response.length; ++i) {
                // uid read time snippet
                let cols = response[i].split('\t');
                if (page1 != 0 || i != 2) {
                    list.insertAdjacentHTML('beforeend', '<hr />');
                }
                list.insertAdjacentHTML('beforeend', threadEntry(cols));
            }
            page2 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 2 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
        }
    );
}

/*
 * Get users
 */
function getUsers() {
    let list = document.getElementById('list');
    let loading = document.getElementById('loading');
    let more = document.getElementById('more');
    if (page1 == 0) {
        list.innerHTML = '';
        more.onclick = function () {getUsers();};
    }
    loading.style.display = 'block';
    more.style.display = 'none';
    request('/api/get_users.php', 'page=' + page1).then(
        function(response) {
            if (response[0] != 'ok') {
                loading.style.display = 'none';
                more.style.display = 'block';
                window.history.back();
                return;
            }
            // page
            let header = response[1].split('\t');
            let cols = response[2].split('\t');
            notification1 = cols[0];
            notification2 = cols[1];
            showNotifications();
            for (let i = 3; i != response.length; ++i) {
                // uid
                list.insertAdjacentHTML('beforeend',
                  '<img class="likeuser" src="' + AMAZON + 'u/' + response[i] + '.jpg" onerror="this.src = \'' + AMAZON + 'res/user.png\'" onclick="location.href = \'posts.php?uid=' + response[i] + '\'" />');
            }
            page1 = parseInt(header[0]) + 1;
            loading.style.display = 'none';
            more.style.display = (response.length - 4 < 100) ? 'none' : 'block';
        },
        function(error) {
            loading.style.display = 'none';
            more.style.display = 'block';
        }
    );
}
