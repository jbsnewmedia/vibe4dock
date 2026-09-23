(function() {
    "use strict";
    var BOT_NAME = "Veronica";
    var USERS_KEY = "vibe4dock.veronica.users";
    var SETTINGS_KEY = "vibe4dock.veronica.settings";
    var USER_KEY = "vibe4dock.veronica.user";
    var REMEMBER_KEY = "vibe4dock.veronica.remember";
    var LAST_KEY = "vibe4dock.veronica.last.";
    var SEEN_KEY = "vibe4dock.veronica.seen.";
    var SESSIONS_KEY = "vibe4dock.veronica.sessions.";
    var MEMBERS_KEY = "vibe4dock.veronica.members.";
    var MODEL_KEY = "vibe4dock.veronica.model";
    var PLAN_KEY = "vibe4dock.veronica.planmode";
    var UPLOADS_KEY = "vibe4dock.veronica.uploads.";
    var STUCK_MINUTES = (window.CHAT_CONFIG && window.CHAT_CONFIG.stuckMinutes) || 10;
    var STUCK_POLLS = Math.max(1, Math.round(STUCK_MINUTES * 60000 / 1500));
    var state = {
        user: null,
        planMode: storageGet(PLAN_KEY) === "1",
        backend: "mock",
        sessions: [],
        sessionId: null,
        generating: false,
        forcedIdle: false,
        recording: null,
        commands: [ {
            name: "new",
            description: t("cmdNew")
        }, {
            name: "help",
            description: t("cmdHelp")
        }, {
            name: "clear",
            description: t("cmdClear")
        } ],
        stalePolls: 0,
        lastGenSig: null,
        stuckShown: false,
        pendingQuestions: [],
        pendingPermissions: [],
        replyError: "",
        lastPendingSig: null,
        lastRendered: null,
        lastMsgs: [],
        incomingCache: [],
        incomingLoaded: false,
        queue: [],
        qAnsweredCount: {},
        qPendingAnswers: {}
    };
    var el = {
        loginScreen: document.getElementById("login-screen"),
        loginCard: document.getElementById("login-card"),
        loginSub: document.getElementById("login-sub"),
        loginAlias: document.getElementById("login-alias"),
        loginPin: document.getElementById("login-pin"),
        loginPin2Wrap: document.getElementById("login-pin2-wrap"),
        loginPin2: document.getElementById("login-pin2"),
        loginRemember: document.getElementById("login-remember"),
        loginError: document.getElementById("login-error"),
        loginSubmit: document.getElementById("login-submit"),
        loginToggle: document.getElementById("login-toggle"),
        app: document.getElementById("app"),
        messages: document.getElementById("messages"),
        pending: document.getElementById("pending"),
        stuck: document.getElementById("stuck"),
        prompt: document.getElementById("prompt"),
        composer: document.getElementById("composer"),
        sendBtn: document.getElementById("send-btn"),
        micBtn: document.getElementById("mic-btn"),
        stopBtn: document.getElementById("stop-btn"),
        attachBtn: document.getElementById("attach-btn"),
        fileInput: document.getElementById("file-input"),
        fileInputGallery: document.getElementById("file-input-gallery"),
        attachMenu: document.getElementById("attach-menu"),
        sessionList: document.getElementById("session-list"),
        sessionTitle: document.getElementById("session-title"),
        chatActions: document.getElementById("chat-actions"),
        chatMenuBtn: document.getElementById("chat-menu-btn"),
        chatMenu: document.getElementById("chat-menu"),
        chatExportBtn: document.getElementById("chat-export-btn"),
        chatDeleteBtn: document.getElementById("chat-delete-btn"),
        chatRemoveBtn: document.getElementById("chat-remove-btn"),
        modelMenuBtn: document.getElementById("model-menu-btn"),
        modelBadge: document.getElementById("model-badge"),
        planModeBtn: document.getElementById("plan-mode-btn"),
        modelMenu: document.getElementById("model-menu"),
        chatModelItems: document.getElementById("model-menu-items"),
        newChat: document.getElementById("new-chat"),
        searchInput: document.getElementById("search-input"),
        sidebar: document.getElementById("sidebar"),
        toggleSidebar: document.getElementById("toggle-sidebar"),
        sidebarCollapse: document.getElementById("sidebar-collapse"),
        toggleSidebarRight: document.getElementById("toggle-sidebar-right"),
        sidebarBackdrop: document.getElementById("sidebar-backdrop"),
        chatAddBtn: document.getElementById("chat-add-btn"),
        logout: document.getElementById("logout"),
        meAvatar: document.getElementById("me-avatar"),
        meAlias: document.getElementById("me-alias"),
        typingIndicator: document.getElementById("typing-indicator"),
        recordingBar: document.getElementById("recording-bar"),
        recordingTimer: document.querySelector("#recording-bar .timer"),
        cancelRecBtn: document.querySelector("#recording-bar .cancel-rec"),
        sendRecBtn: document.querySelector("#recording-bar .send-rec"),
        questionModal: document.getElementById("question-modal"),
        questionModalBody: document.getElementById("question-modal-body"),
        questionModalCount: document.getElementById("question-modal-count"),
        questionModalClose: document.getElementById("question-modal-close"),
        adminUsersBtn: document.getElementById("admin-users-btn"),
        adminModal: document.getElementById("admin-modal"),
        adminModalClose: document.getElementById("admin-modal-close"),
        adminUserList: document.getElementById("admin-user-list"),
        adminAddForm: document.getElementById("admin-add-form"),
        adminAllowReg: document.getElementById("admin-allow-reg"),
        adminNewAlias: document.getElementById("admin-new-alias"),
        adminNewPin: document.getElementById("admin-new-pin"),
        adminNewAdmin: document.getElementById("admin-new-admin"),
        meAdminBadge: document.getElementById("me-admin-badge"),
        appDialog: document.getElementById("app-dialog"),
        appDialogTitle: document.getElementById("app-dialog-title"),
        appDialogBody: document.getElementById("app-dialog-body"),
        appDialogInput: document.getElementById("app-dialog-input"),
        appDialogSelect: document.getElementById("app-dialog-select"),
        appDialogCancel: document.getElementById("app-dialog-cancel"),
        appDialogOk: document.getElementById("app-dialog-ok"),
        uploadStatus: document.getElementById("upload-status"),
        dropOverlay: document.getElementById("drop-overlay"),
        atPopup: document.getElementById("at-popup"),
        cmdPopup: document.getElementById("cmd-popup"),
        statusText: document.getElementById("status-text"),
        chatMain: document.getElementById("chat")
    };
    function storageGet(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }
    function storageSet(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {}
    }
    function storageDel(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {}
    }
    function sessionGet(key) {
        try {
            return sessionStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }
    function sessionSet(key, value) {
        try {
            sessionStorage.setItem(key, value);
        } catch (e) {}
    }
    function sessionDel(key) {
        try {
            sessionStorage.removeItem(key);
        } catch (e) {}
    }
    function makeId() {
        return "ses_" + Math.random().toString(36).slice(2, 12);
    }
    function makeMsgId() {
        return "msg_" + Date.now() + "_" + Math.random().toString(36).slice(2, 6);
    }
    function esc(s) {
        var AMP = "&" + "amp;";
        var LT = "&" + "lt;";
        var GT = "&" + "gt;";
        var QUOT = "&" + "quot;";
        return String(s == null ? "" : s).replace(/&/g, AMP).replace(/</g, LT).replace(/>/g, GT).replace(/"/g, QUOT);
    }
    function hashString(str) {
        var hash = 0;
        for (var i = 0; i < str.length; i++) {
            hash = (hash << 5) - hash + str.charCodeAt(i);
            hash |= 0;
        }
        return hash;
    }
    function getInitials(name) {
        if (!name) return "#";
        var parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
        return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
    }
    function formatTimeShort(ts) {
        var d = new Date(ts);
        var now = new Date;
        var sameDay = d.toDateString() === now.toDateString();
        var h = d.getHours().toString().padStart(2, "0");
        var m = d.getMinutes().toString().padStart(2, "0");
        if (sameDay) return h + ":" + m;
        var day = d.getDate().toString().padStart(2, "0");
        var mon = (d.getMonth() + 1).toString().padStart(2, "0");
        return day + "." + mon + " " + h + ":" + m;
    }
    function formatTime(d) {
        var h = d.getHours().toString().padStart(2, "0");
        var m = d.getMinutes().toString().padStart(2, "0");
        return h + ":" + m;
    }
    function formatDuration(sec) {
        var mm = Math.floor(sec / 60).toString().padStart(2, "0");
        var ss = (sec % 60).toString().padStart(2, "0");
        return mm + ":" + ss;
    }
    function formatDateLabel(d) {
        var today = new Date;
        var yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        if (d.toDateString() === today.toDateString()) return t("dateToday");
        if (d.toDateString() === yesterday.toDateString()) return t("dateYesterday");
        var diff = (today - d) / (1e3 * 60 * 60 * 24);
        if (diff < 7) {
            var days = I18N.raw("days");
            return days[d.getDay()];
        }
        var dd = d.getDate().toString().padStart(2, "0");
        var mm = (d.getMonth() + 1).toString().padStart(2, "0");
        var yyyy = d.getFullYear();
        return dd + "." + mm + "." + yyyy;
    }
    var loginMode = "login";
    var userCache = null;
    function loadUsers() {
        if (userCache === null) {
            var raw = storageGet(USERS_KEY);
            userCache = {};
            if (raw) {
                try {
                    userCache = JSON.parse(raw) || {};
                } catch (e) {
                    userCache = {};
                }
            }
        }
        return userCache;
    }
    function saveUsers(users) {
        userCache = users;
        storageSet(USERS_KEY, JSON.stringify(users));
        usersApiPut(users);
    }
    function usersApiPut(users) {
        try {
            fetch(API_BASE + "/users", {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(users)
            }).catch(function(err) {
                if (window.console && console.warn) console.warn("Veronica: User-DB Sync fehlgeschlagen", err);
            });
        } catch (e) {}
    }
    function syncUsersFromServer() {
        return fetch(API_BASE + "/users", {
            cache: "no-store"
        }).then(function(r) {
            if (!r.ok) throw new Error("HTTP " + r.status);
            return r.json();
        }).then(function(serverUsers) {
            if (serverUsers && typeof serverUsers === "object" && Object.keys(serverUsers).length) {
                userCache = serverUsers;
                storageSet(USERS_KEY, JSON.stringify(serverUsers));
                return;
            }
            var local = loadUsers();
            if (Object.keys(local).length) usersApiPut(local);
        }).catch(function(err) {
            if (window.console && console.warn) console.warn("Veronica: User-DB nicht erreichbar, nutze localStorage", err);
        });
    }
    function loadSettings() {
        var raw = storageGet(SETTINGS_KEY);
        if (!raw) return {};
        try {
            return JSON.parse(raw) || {};
        } catch (e) {
            return {};
        }
    }
    function saveSettings(s) {
        storageSet(SETTINGS_KEY, JSON.stringify(s));
    }
    function registrationAllowed() {
        var s = loadSettings();
        if (s && typeof s.allowRegistration === "boolean") return s.allowRegistration;
        var v = window.CHAT_CONFIG && typeof window.CHAT_CONFIG.allowRegistration !== "undefined" ? window.CHAT_CONFIG.allowRegistration : "1";
        return String(v) !== "0" && String(v) !== "false" && String(v) !== "no";
    }
    function isAdminAlias(alias) {
        var users = loadUsers();
        return !!(users[alias] && users[alias].admin);
    }
    function ensureAdminExists() {
        var users = loadUsers();
        var aliases = Object.keys(users);
        if (!aliases.length) return;
        for (var i = 0; i < aliases.length; i++) {
            if (users[aliases[i]].admin) return;
        }
        var oldest = aliases[0];
        for (i = 1; i < aliases.length; i++) {
            if ((users[aliases[i]].createdAt || 0) < (users[oldest].createdAt || 0)) oldest = aliases[i];
        }
        users[oldest].admin = true;
        saveUsers(users);
    }
    var dialogResolve = null;
    function settleDialog(val) {
        if (dialogResolve) dialogResolve(val);
    }
    function showAppDialog(opts) {
        var o = opts || {};
        return new Promise(function(resolve) {
            el.appDialogTitle.textContent = o.title || t("dialogConfirm");
            el.appDialogBody.textContent = o.message || "";
            el.appDialogOk.textContent = o.okLabel || "OK";
            el.appDialogOk.classList.toggle("danger", !!o.danger);
            el.appDialogOk.classList.toggle("primary", !o.danger);
            el.appDialogCancel.hidden = !o.cancelLabel;
            el.appDialogCancel.textContent = o.cancelLabel || t("dialogCancel");
            if (o.input) {
                el.appDialogInput.hidden = false;
                el.appDialogInput.value = o.value || "";
                el.appDialogInput.placeholder = o.placeholder || "";
            } else {
                el.appDialogInput.hidden = true;
                el.appDialogInput.value = "";
            }
            if (o.select && o.select.length) {
                el.appDialogSelect.hidden = false;
                el.appDialogSelect.innerHTML = o.select.map(function(opt) {
                    return '<option value="' + esc(opt.value) + '">' + esc(opt.label) + "</option>";
                }).join("");
            } else {
                el.appDialogSelect.hidden = true;
                el.appDialogSelect.innerHTML = "";
            }
            el.appDialog.hidden = false;
            dialogResolve = function(val) {
                dialogResolve = null;
                el.appDialog.hidden = true;
                resolve(val);
            };
            if (o.input) {
                setTimeout(function() {
                    el.appDialogInput.focus();
                }, 50);
            }
        });
    }
    function dialogValue(o) {
        if (o.input) return el.appDialogInput.value;
        if (o.select && o.select.length) return el.appDialogSelect.value;
        return true;
    }
    function appConfirm(message, opts) {
        var o = opts && typeof opts === "object" ? opts : {};
        o.message = message;
        o.title = o.title || t("dialogConfirm");
        o.okLabel = o.okLabel || t("dialogConfirm");
        o.cancelLabel = o.cancelLabel || t("dialogCancel");
        return showAppDialog(o).then(function(ok) {
            return ok ? dialogValue(o) : null;
        });
    }
    function appAlert(message, title) {
        return showAppDialog({
            title: title || t("dialogNotice"),
            message: message,
            okLabel: "OK"
        });
    }
    function adminOpen() {
        if (!state.user || !state.user.admin) return;
        ensureAdminExists();
        el.adminModal.hidden = false;
        syncRegistrationControl();
        renderAdminUsers();
    }
    function adminClose() {
        el.adminModal.hidden = true;
    }
    function adminGuard(alias) {
        var users = loadUsers();
        if (!users[alias]) return null;
        if (users[alias].admin) {
            var admins = Object.keys(users).filter(function(a) {
                return users[a].admin;
            }).length;
            if (admins <= 1) return t("adminKeepOne");
        }
        return null;
    }
    function renderAdminUsers() {
        if (!el.adminUserList) return;
        var users = loadUsers();
        var aliases = Object.keys(users).sort();
        if (!aliases.length) {
            el.adminUserList.innerHTML = '<div class="admin-empty">' + t("adminEmpty") + "</div>";
            return;
        }
        var out = "";
        aliases.forEach(function(a) {
            var u = users[a] || {};
            var created = u.createdAt ? new Date(u.createdAt).toLocaleString() : "–";
            var isMe = state.user && state.user.alias === a;
            out += '<div class="admin-user-row">' + '<div class="admin-user-main">' + '<span class="admin-user-alias">' + esc(a) + "</span>" + (u.admin ? '<span class="admin-badge">Admin</span>' : "") + (isMe ? '<span class="admin-badge self">Du</span>' : "") + "</div>" + '<div class="admin-user-meta">angelegt: ' + esc(created) + "</div>" + '<div class="admin-user-actions">' + '<button type="button" class="admin-act" data-act="pin" data-alias="' + esc(a) + '">PIN ändern</button>' + '<button type="button" class="admin-act" data-act="admin" data-alias="' + esc(a) + '">' + (u.admin ? "Admin entfernen" : "Zum Admin machen") + "</button>" + '<button type="button" class="admin-act danger" data-act="delete" data-alias="' + esc(a) + '">Löschen</button>' + "</div>" + "</div>";
        });
        el.adminUserList.innerHTML = out;
    }
    function adminChangePin(alias) {
        var users = loadUsers();
        if (!users[alias]) return;
        appConfirm(t("newPinFor", {
            alias: alias
        }), {
            title: t("pinChangeTitle"),
            input: true,
            placeholder: t("newPinPh"),
            okLabel: t("save")
        }).then(function(result) {
            if (result === null) return;
            var pin = String(result);
            if (pin.length < 4) {
                appAlert(t("pinTooShort"));
                return;
            }
            hashPin(alias, pin).then(function(hash) {
                users = loadUsers();
                if (!users[alias]) return;
                users[alias].pin = hash;
                saveUsers(users);
                renderAdminUsers();
            });
        });
    }
    function adminToggleAdmin(alias) {
        var blocked = adminGuard(alias);
        if (blocked) {
            appAlert(blocked);
            return;
        }
        var users = loadUsers();
        if (!users[alias]) return;
        users[alias].admin = !users[alias].admin;
        saveUsers(users);
        if (state.user && state.user.alias === alias) {
            state.user.admin = !!users[alias].admin;
            renderUserBadge();
        }
        renderAdminUsers();
    }
    function syncRegistrationControl() {
        if (!el.adminAllowReg) return;
        el.adminAllowReg.checked = registrationAllowed();
    }
    function adminSetRegistration(allowed) {
        var s = loadSettings();
        s.allowRegistration = !!allowed;
        saveSettings(s);
        if (!el.app.hidden) return;
        if (!allowed && loginMode === "register") {
            setLoginMode("login");
        } else {
            el.loginToggle.hidden = !registrationAllowed();
        }
    }
    function adminDeleteUser(alias) {
        var users = loadUsers();
        if (!users[alias]) return;
        if (state.user && state.user.alias === alias) {
            appAlert(t("selfDelete"));
            return;
        }
        var blocked = adminGuard(alias);
        if (blocked) {
            appAlert(blocked);
            return;
        }
        appConfirm(t("deleteUserMsg", {
            alias: alias
        }), {
            title: t("deleteUserTitle"),
            danger: true,
            okLabel: t("adminDelete")
        }).then(function(ok) {
            if (!ok) return;
            users = loadUsers();
            if (!users[alias]) return;
            delete users[alias];
            saveUsers(users);
            renderAdminUsers();
        });
    }
    function handleAdminAddSubmit(e) {
        e.preventDefault();
        var alias = normalizeAlias(el.adminNewAlias.value);
        var pin = el.adminNewPin.value || "";
        if (!isValidAlias(alias)) {
            appAlert(t("aliasInvalid"));
            el.adminNewAlias.focus();
            return;
        }
        if (pin.length < 4) {
            appAlert(t("pinTooShort"));
            el.adminNewPin.focus();
            return;
        }
        var users = loadUsers();
        if (users[alias]) {
            appAlert(t("aliasTaken", {
                alias: alias
            }));
            return;
        }
        var wantAdmin = !!(el.adminNewAdmin && el.adminNewAdmin.checked);
        hashPin(alias, pin).then(function(hash) {
            users = loadUsers();
            if (users[alias]) {
                appAlert(t("aliasTaken", {
                    alias: alias
                }));
                return;
            }
            users[alias] = {
                pin: hash,
                createdAt: Date.now()
            };
            if (wantAdmin) users[alias].admin = true;
            saveUsers(users);
            el.adminNewAlias.value = "";
            el.adminNewPin.value = "";
            if (el.adminNewAdmin) el.adminNewAdmin.checked = false;
            renderAdminUsers();
        });
    }
    function normalizeAlias(v) {
        return String(v == null ? "" : v).trim().toLowerCase();
    }
    function isValidAlias(v) {
        return /^[a-z0-9äöüß][a-z0-9äöüß_-]{0,2}$/.test(v);
    }
    function hashPin(alias, pin) {
        var str = "veronica:" + alias + ":" + pin;
        if (window.crypto && window.crypto.subtle && window.TextEncoder && typeof window.crypto.subtle.digest === "function") {
            return window.crypto.subtle.digest("SHA-256", (new TextEncoder).encode(str)).then(function(buf) {
                var bytes = new Uint8Array(buf);
                var hex = "";
                for (var i = 0; i < bytes.length; i++) {
                    hex += bytes[i].toString(16).padStart(2, "0");
                }
                return "sha256:" + hex;
            }).catch(function() {
                return "fnv:" + fnvHash(str);
            });
        }
        return Promise.resolve("fnv:" + fnvHash(str));
    }
    function fnvHash(s) {
        var h1 = 3735928559 ^ s.length, h2 = 1103547991 ^ s.length;
        for (var i = 0; i < s.length; i++) {
            var ch = s.charCodeAt(i);
            h1 = Math.imul(h1 ^ ch, 2654435761);
            h2 = Math.imul(h2 ^ ch, 1597334677);
        }
        h1 = Math.imul(h1 ^ h1 >>> 16, 2246822507) ^ Math.imul(h2 ^ h2 >>> 13, 3266489909);
        h2 = Math.imul(h2 ^ h2 >>> 16, 2246822507) ^ Math.imul(h1 ^ h1 >>> 13, 3266489909);
        return (4294967296 * (2097151 & h2) + (h1 >>> 0)).toString(16);
    }
    function setLoginMode(mode) {
        loginMode = mode;
        var register = mode === "register";
        el.loginToggle.hidden = !registrationAllowed();
        el.loginPin2Wrap.hidden = !register;
        el.loginSubmit.textContent = register ? t("register") : t("login");
        el.loginToggle.textContent = register ? t("toLogin") : t("toRegister");
        el.loginSub.textContent = register ? t("subRegister") : t("subLogin");
        el.loginPin.setAttribute("autocomplete", register ? "new-password" : "current-password");
        hideLoginError();
        (register ? el.loginAlias : el.loginAlias).focus();
    }
    function showLoginError(msg) {
        el.loginError.textContent = msg;
        el.loginError.hidden = false;
    }
    function hideLoginError() {
        el.loginError.hidden = true;
        el.loginError.textContent = "";
    }
    function handleLoginSubmit(e) {
        e.preventDefault();
        hideLoginError();
        var alias = normalizeAlias(el.loginAlias.value);
        var pin = el.loginPin.value || "";
        var pin2 = el.loginPin2.value || "";
        if (!isValidAlias(alias)) {
            showLoginError(t("aliasInvalid"));
            el.loginAlias.focus();
            return;
        }
        if (pin.length < 4) {
            showLoginError(t("pinTooShort"));
            el.loginPin.focus();
            return;
        }
        var users = loadUsers();
        var existing = users[alias];
        if (loginMode === "register") {
            if (!registrationAllowed()) {
                showLoginError(t("regDisabled"));
                return;
            }
            if (existing) {
                showLoginError(t("aliasTakenLogin", {
                    alias: alias
                }));
                return;
            }
            if (pin2 !== pin) {
                showLoginError(t("pinMismatch"));
                el.loginPin2.focus();
                return;
            }
            hashPin(alias, pin).then(function(hash) {
                var isFirstUser = Object.keys(users).length === 0;
                users[alias] = {
                    pin: hash,
                    createdAt: Date.now()
                };
                if (isFirstUser) users[alias].admin = true;
                saveUsers(users);
                loginAs(alias, hash, el.loginRemember.checked);
            });
            return;
        }
        if (!existing) {
            showLoginError(t("noAccount", {
                alias: alias
            }));
            return;
        }
        hashPin(alias, pin).then(function(hash) {
            if (hash !== existing.pin) {
                showLoginError(t("wrongPin"));
                el.loginPin.select();
                return;
            }
            loginAs(alias, hash, el.loginRemember.checked);
        });
    }
    function loadRemembered(users) {
        var raw = storageGet(REMEMBER_KEY);
        if (!raw) {
            return null;
        }
        var r = null;
        try {
            r = JSON.parse(raw);
        } catch (e) {
            r = null;
        }
        if (!r || !r.alias || !r.pinHash || !users[r.alias]) {
            storageDel(REMEMBER_KEY);
            return null;
        }
        if (users[r.alias].pin !== r.pinHash) {
            storageDel(REMEMBER_KEY);
            return null;
        }
        return r;
    }
    function loginAs(alias, pinHash, remember) {
        state.user = {
            alias: alias,
            admin: isAdminAlias(alias)
        };
        sessionSet(USER_KEY, alias);
        if (remember && pinHash) {
            storageSet(REMEMBER_KEY, JSON.stringify({
                alias: alias,
                pinHash: pinHash,
                ts: Date.now()
            }));
        } else {
            storageDel(REMEMBER_KEY);
        }
        if (el.loginRemember) {
            el.loginRemember.checked = false;
        }
        el.loginPin.value = "";
        el.loginPin2.value = "";
        hideLoginError();
        bootApp();
    }
    function logout() {
        var doLogout = function() {
            sessionDel(USER_KEY);
            storageDel(REMEMBER_KEY);
            state.user = null;
            state.sessions = [];
            state.sessionId = null;
            state.generating = false;
            state.forcedIdle = false;
            clearQueuedMessages();
            resetRenderedMsgKeys();
            state.lastRendered = null;
            state.lastMsgs = [];
            state.pendingQuestions = [];
            state.pendingPermissions = [];
            state.lastPendingSig = null;
            state.stalePolls = 0;
            state.lastGenSig = null;
            state.replyError = "";
            hideStuck();
            setTyping(false);
            el.app.hidden = true;
            el.loginScreen.hidden = false;
            el.loginAlias.value = "";
            el.loginPin.value = "";
            el.loginPin2.value = "";
            setLoginMode("login");
            el.prompt.value = "";
            el.prompt.style.height = "auto";
            setTimeout(function() {
                el.loginAlias.focus();
            }, 60);
        };
        if (state.generating) {
            appConfirm(t("logoutConfirm"), {
                title: t("logoutTitle"),
                danger: true,
                okLabel: t("logoutTitle")
            }).then(function(ok) {
                if (ok) doLogout();
            });
            return;
        }
        doLogout();
    }
    var BASE = location.pathname.replace(/\/[^/]*$/, "/");
    var API_BASE = location.origin + BASE + "api";
    function api(method, path, body, timeoutMs) {
        var ctrl = typeof AbortController !== "undefined" ? new AbortController : null;
        var timer = setTimeout(function() {
            if (ctrl) ctrl.abort();
        }, timeoutMs || 15e3);
        return fetch(API_BASE + path, {
            method: method,
            headers: body ? {
                "Content-Type": "application/json"
            } : undefined,
            body: body ? JSON.stringify(body) : undefined,
            signal: ctrl ? ctrl.signal : undefined
        }).then(function(r) {
            clearTimeout(timer);
            if (!r.ok) {
                throw new Error("HTTP " + r.status + " on " + path);
            }
            var ct = r.headers.get("content-type") || "";
            return ct.indexOf("json") !== -1 ? r.json() : r.text();
        }).catch(function(err) {
            clearTimeout(timer);
            throw err;
        });
    }
    function detectBackend(attempt) {
        if (location.protocol === "file:") {
            state.backend = "mock";
            return Promise.resolve();
        }
        return api("GET", "/session", null, 2500).then(function() {
            state.backend = "api";
        }).catch(function() {
            if ((attempt || 0) < 3) {
                return new Promise(function(r) {
                    setTimeout(r, 1e3);
                }).then(function() {
                    return detectBackend((attempt || 0) + 1);
                });
            }
            state.backend = "mock";
        });
    }
    function tagPrefix() {
        return "[" + (state.user ? state.user.alias : "?") + "]";
    }
    function taggedTitle(display) {
        return tagPrefix() + " " + (display || t("sessionNew"));
    }
    function stripTag(title) {
        var t = String(title == null ? "" : title);
        var p = tagPrefix() + " ";
        return t.indexOf(p) === 0 ? t.slice(p.length) : t;
    }
    function isOwnSession(raw) {
        return String(raw && raw.title || "").indexOf(tagPrefix() + " ") === 0;
    }
    function isMemberOfSession(raw) {
        var me = state.user ? state.user.alias : "";
        return !!me && String(raw && raw.title || "").indexOf("[" + me + "]") !== -1;
    }
    function displayTitle(raw) {
        var t = String(raw == null ? "" : raw).replace(/\s*\[[a-z0-9äöüß_-]{1,3}\]/g, " ");
        t = t.replace(/\s+/g, " ").trim();
        return t || I18N.t("sessionNew");
    }
    function membersFromTitle(raw) {
        var me = state.user ? state.user.alias : "";
        var tags = String(raw == null ? "" : raw).match(/\[([a-z0-9äöüß_-]{1,3})\]/g) || [];
        var out = [];
        tags.forEach(function(tag) {
            var a = tag.slice(1, -1);
            if (a !== me && out.indexOf(a) === -1) out.push(a);
        });
        return out;
    }
    function renameSession(id, title) {
        return api("PATCH", "/session/" + encodeURIComponent(id), {
            title: title
        }).catch(function() {});
    }
    function loadSeen() {
        var raw = storageGet(SEEN_KEY + (state.user ? state.user.alias : ""));
        if (!raw) return {};
        try {
            return JSON.parse(raw) || {};
        } catch (e) {
            return {};
        }
    }
    function saveSeen(map) {
        storageSet(SEEN_KEY + (state.user ? state.user.alias : ""), JSON.stringify(map));
    }
    function markSeen(sessionId) {
        var map = loadSeen();
        map[sessionId] = Date.now();
        saveSeen(map);
    }
    function sessionStoreKey() {
        return SESSIONS_KEY + (state.user ? state.user.alias : "");
    }
    function loadMockSessions() {
        var raw = storageGet(sessionStoreKey());
        if (!raw) return [];
        try {
            return JSON.parse(raw) || [];
        } catch (e) {
            return [];
        }
    }
    function saveMockSessions() {
        storageSet(sessionStoreKey(), JSON.stringify(state.sessions));
    }
    function adoptSession(raw) {
        var t = raw && raw.time || {};
        return {
            id: raw.id,
            title: displayTitle(raw.title),
            members: membersFromTitle(raw.title),
            updatedAt: t.updated || t.created || Date.now(),
            raw: raw
        };
    }
    var deletedSessionTombstones = [];
    function markSessionDeleted(id) {
        deletedSessionTombstones.push({
            id: id,
            until: Date.now() + 1e4
        });
    }
    function isSessionDeleted(id) {
        var now = Date.now();
        deletedSessionTombstones = deletedSessionTombstones.filter(function(t) {
            return t.until > now;
        });
        return deletedSessionTombstones.some(function(t) {
            return t.id === id;
        });
    }
    function loadSessions() {
        if (state.backend === "api") {
            return api("GET", "/session").then(function(list) {
                state.sessions = (list || []).filter(function(s) {
                    return s && !s.parentID && (isOwnSession(s) || isMemberOfSession(s)) && !isSessionDeleted(s.id);
                }).map(adoptSession).sort(function(a, b) {
                    return b.updatedAt - a.updatedAt;
                });
                renderSessionList();
            });
        }
        state.sessions = loadMockSessions().sort(function(a, b) {
            return (b.updatedAt || 0) - (a.updatedAt || 0);
        });
        renderSessionList();
        return Promise.resolve();
    }
    function createSession(displayTitle) {
        if (state.backend === "api") {
            return api("POST", "/session", {
                title: taggedTitle(displayTitle)
            }).then(function(info) {
                var s = adoptSession(info);
                state.sessions.unshift(s);
                state.sessionId = s.id;
                rememberSession();
                renderSessionList();
                ensurePendingUploads();
                return s;
            });
        }
        var s = {
            id: makeId(),
            title: displayTitle || t("sessionNew"),
            createdAt: Date.now(),
            updatedAt: Date.now(),
            unreadCount: 0,
            messages: []
        };
        state.sessions.unshift(s);
        state.sessionId = s.id;
        rememberSession();
        saveMockSessions();
        renderSessionList();
        ensurePendingUploads();
        return Promise.resolve(s);
    }
    function rememberSession() {
        if (state.user && state.sessionId) {
            sessionSet(LAST_KEY + state.user.alias, state.sessionId);
        }
    }
    function findSession(id) {
        for (var i = 0; i < state.sessions.length; i++) {
            if (state.sessions[i].id === id) return state.sessions[i];
        }
        return null;
    }
    function getCurrentSession() {
        return findSession(state.sessionId);
    }
    function deleteSession(id) {
        markSessionDeleted(id);
        var after = function() {
            state.sessions = state.sessions.filter(function(s) {
                return s.id !== id;
            });
            if (state.sessionId === id) {
                state.sessionId = null;
                state.lastRendered = null;
                state.lastMsgs = [];
            }
            if (state.backend === "mock") saveMockSessions();
            renderSessionList();
            clearQueuedMessages();
            renderMessages(state.lastMsgs);
        };
        if (state.backend === "api") {
            api("DELETE", "/session/" + encodeURIComponent(id), {}).then(after).catch(after);
        } else {
            after();
        }
    }
    function toggleChatMenu(force) {
        if (!el.chatMenu) return;
        var show = typeof force === "boolean" ? force : el.chatMenu.hidden;
        el.chatMenu.hidden = !show;
        if (show) {
            closeModelMenu();
        }
    }
    function closeChatMenu() {
        if (el.chatMenu) el.chatMenu.hidden = true;
    }
    function toggleModelMenu() {
        if (!el.modelMenu) return;
        var show = el.modelMenu.hidden;
        el.modelMenu.hidden = !show;
        if (show) {
            closeChatMenu();
            renderModelMenu();
            loadModelsForMenu();
        }
    }
    function closeModelMenu() {
        if (el.modelMenu) el.modelMenu.hidden = true;
    }
    function exportSession() {
        closeChatMenu();
        var msgs = state.lastMsgs || [];
        var s = getCurrentSession();
        if (!msgs.length && s && s.messages) msgs = s.messages;
        if (!msgs.length) {
            appAlert(t("exportNone"));
            return;
        }
        var title = el.sessionTitle && el.sessionTitle.textContent || "Chat";
        var lines = [ t("exportTitle"), "Chat: " + title, t("exportAt") + (new Date).toLocaleString(), "" ];
        msgs.forEach(function(m) {
            var info = m.info || m;
            var role = info.role === "user" ? "Du" : "Veronica";
            var ts = info.time && (info.time.completed || info.time.created) || "";
            var text = "";
            (m.parts || []).forEach(function(p) {
                if (p.type === "text" && p.text) text += (text ? "\n" : "") + p.text;
            });
            if (!text) return;
            lines.push("[" + (ts ? new Date(ts).toLocaleString() : "–") + "] " + role + ":");
            lines.push(text);
            lines.push("");
        });
        var blob = new Blob([ lines.join("\n") ], {
            type: "text/markdown;charset=utf-8"
        });
        var a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = "veronica-" + (state.sessionId ? String(state.sessionId).replace(/[^a-z0-9_-]/gi, "").slice(0, 12) : "chat") + ".md";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(function() {
            URL.revokeObjectURL(a.href);
        }, 2e3);
    }
    function deleteCurrentSession() {
        closeChatMenu();
        if (!state.sessionId) {
            appAlert(t("chatDeleteNone"));
            return;
        }
        var id = state.sessionId;
        appConfirm(t("chatDeleteAsk"), {
            title: t("chatDeleteTitle"),
            danger: true,
            okLabel: t("adminDelete")
        }).then(function(ok) {
            if (ok) deleteSession(id);
        });
    }
    function openAddMember() {
        closeChatMenu();
        var sid = state.sessionId;
        if (!sid) {
            appAlert(t("noActiveChat"));
            return;
        }
        var users = loadUsers();
        var members = getSessionMembers({
            id: sid
        });
        var me = state.user ? state.user.alias : "";
        var options = Object.keys(users).filter(function(a) {
            return a !== me && members.indexOf(a) === -1;
        }).map(function(a) {
            return {
                value: a,
                label: a + (users[a].admin ? " (Admin)" : "")
            };
        });
        if (!options.length) {
            appAlert(t("addNone"));
            return;
        }
        appConfirm(t("addMsg"), {
            title: t("addTitle"),
            select: options,
            okLabel: t("addOk")
        }).then(function(result) {
            if (result === null || !result) return;
            var s = getCurrentSession() || {
                id: sid
            };
            addSessionMember(s, String(result));
            renderSessionList();
        });
    }
    function sessionOwnerAlias(s) {
        var m = String(s && s.raw && s.raw.title || "").match(/^\[([a-z0-9äöüß_-]{1,3})\]/);
        return m ? m[1] : "";
    }
    function openRemoveMember() {
        closeChatMenu();
        var sid = state.sessionId;
        if (!sid) {
            appAlert(t("noActiveChat"));
            return;
        }
        var s = getCurrentSession() || {
            id: sid
        };
        var me = state.user ? state.user.alias : "";
        var owner = sessionOwnerAlias(s);
        var members = getSessionMembers(s).filter(function(a) {
            return a !== me && a !== owner;
        });
        if (!members.length) {
            appAlert(t("removeNone"));
            return;
        }
        var users = loadUsers();
        var options = members.map(function(a) {
            return {
                value: a,
                label: a + (users[a] && users[a].admin ? " (Admin)" : "")
            };
        });
        appConfirm(t("removeMsg"), {
            title: t("removeTitle"),
            select: options,
            okLabel: t("removeOk"),
            danger: true
        }).then(function(result) {
            if (result === null || !result) return;
            removeSessionMember(s, String(result));
            renderSessionList();
        });
    }
    function removeSessionMember(s, alias) {
        if (!s || !s.id || !alias) return;
        var members = getSessionMembers(s).filter(function(a) {
            return a !== alias;
        });
        storageSet(MEMBERS_KEY + s.id, JSON.stringify(members));
        if (s.members) {
            s.members = members;
        }
        if (state.backend === "api" && s.raw && s.raw.title && s.raw.title.indexOf("[" + alias + "]") !== -1) {
            var nt = s.raw.title.replace(" [" + alias + "]", "");
            s.raw.title = nt;
            s.title = displayTitle(nt);
            s.members = membersFromTitle(nt);
            renameSession(s.id, nt).then(function() {
                if (state.sessionId === s.id) {
                    refresh();
                } else {
                    renderSessionList();
                }
            });
        } else if (state.sessionId === s.id) {
            renderSessionList();
        }
    }
    function switchSession(id) {
        state.sessionId = id;
        state.lastRendered = null;
        state.lastMsgs = [];
        state.forcedIdle = false;
        state.stalePolls = 0;
        state.lastGenSig = null;
        clearQueuedMessages();
        state.qAnsweredCount = {};
        state.qPendingAnswers = {};
        resetRenderedMsgKeys();
        hideStuck();
        rememberSession();
        markSeen(id);
        renderSessionList();
        ensurePendingUploads();
        if (state.backend === "api") {
            renderMessages([]);
            refresh();
        } else {
            var s = getCurrentSession();
            state.lastMsgs = s && s.messages || [];
            state.generating = false;
            updateComposerState();
            setTyping(false);
            renderMessages(state.lastMsgs);
        }
        updateSessionTitle();
        if (window.innerWidth < 760) closeSidebar();
        scrollToBottom(true);
    }
    function assistantErrorText(err) {
        if (!err) {
            return t("errPrefix") + "API";
        }
        var data = err.data || {};
        var raw = [ data.message, err.message, data.responseBody, err.name ].map(function(v) {
            return v == null ? "" : String(v);
        }).join(" ");
        if (/FreeTierError|free tier/i.test(raw)) {
            return t("errPrefix") + t("freeTierHint");
        }
        var msg = data.message && String(data.message) || err.message && String(err.message) || data.responseBody && String(data.responseBody) || err.name || "API";
        return t("errPrefix") + msg;
    }
    function renderMessage(m) {
        var info = m.info || m;
        var role = info.role || "user";
        var parts = m.parts || [];
        var bubble = document.createElement("div");
        bubble.className = "bubble";
        var hasContent = false;
        if (info.error && role === "assistant" && info.error.name !== "MessageAbortedError") {
            var ebox = document.createElement("div");
            ebox.className = "assistant-error";
            ebox.textContent = assistantErrorText(info.error);
            bubble.appendChild(ebox);
            hasContent = true;
        }
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i];
            if (p.type === "text" && p.text) {
                bubble.insertAdjacentHTML("beforeend", md(p.text));
                linkifyMentions(bubble);
                hasContent = true;
            } else if (p.type === "voice" && role === "user" && p.duration != null) {
                bubble.appendChild(buildVoiceBubble(p.duration));
                hasContent = true;
            }
        }
        if (!hasContent) return null;
        var meta = document.createElement("span");
        meta.className = "meta";
        var ts = info.time && (info.time.completed || info.time.created) || Date.now();
        var timeSpan = document.createElement("span");
        timeSpan.className = "time";
        timeSpan.textContent = formatTime(new Date(ts));
        meta.appendChild(timeSpan);
        if (role === "user") {
            var ticks = document.createElement("span");
            ticks.className = "ticks delivered";
            ticks.innerHTML = '<svg viewBox="0 0 22 16" width="22" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' + '<polyline points="1 8 5 12 10 5"/>' + '<polyline points="7 8 11 12 21 1"/>' + "</svg>";
            meta.appendChild(ticks);
        }
        bubble.appendChild(meta);
        var wrap = document.createElement("div");
        wrap.className = "msg " + (role === "user" ? "user" : "assistant");
        wrap.appendChild(bubble);
        return wrap;
    }
    function buildVoiceBubble(duration) {
        var wrap = document.createElement("div");
        wrap.className = "voice-bubble";
        var totalBars = 40;
        var bars = [];
        for (var i = 0; i < totalBars; i++) {
            var h = 30 + Math.random() * 70;
            bars.push('<span style="height:' + h + '%"></span>');
        }
        wrap.innerHTML = '<div class="voice-player">' + '<button class="play-btn" type="button">' + '<svg class="play-icon" viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>' + "</button>" + '<div class="voice-bar">' + '<div class="voice-progress"></div>' + '<div class="voice-wave">' + bars.join("") + "</div>" + "</div>" + '<span class="voice-time">' + formatDuration(duration) + "</span>" + "</div>";
        var playBtn = wrap.querySelector(".play-btn");
        var playIcon = wrap.querySelector(".play-icon");
        var progress = wrap.querySelector(".voice-progress");
        var playing = false;
        var interval = null;
        var pct = 0;
        playBtn.addEventListener("click", function() {
            if (playing) {
                playing = false;
                playIcon.innerHTML = '<path d="M8 5v14l11-7z"/>';
                clearInterval(interval);
            } else {
                playing = true;
                playIcon.innerHTML = '<path d="M6 4h4v16H6zm8 0h4v16h-4z"/>';
                interval = setInterval(function() {
                    pct += 2;
                    if (pct >= 100) {
                        pct = 100;
                        playing = false;
                        playIcon.innerHTML = '<path d="M8 5v14l11-7z"/>';
                        clearInterval(interval);
                    }
                    progress.style.width = pct + "%";
                }, 200);
            }
        });
        return wrap;
    }
    var renderedMsgKeys = {};
    function resetRenderedMsgKeys() {
        renderedMsgKeys = {};
    }
    function visibleSig(msgs) {
        var parts = [];
        (msgs || []).forEach(function(m) {
            var info = m.info || m;
            var t = "";
            (m.parts || []).forEach(function(p) {
                if (p.type === "text" && p.text) t += p.text;
            });
            parts.push((info.id || "") + ":" + info.role + ":" + (info.time && info.time.completed ? 1 : 0) + ":" + t.length + ":" + hashString(t));
        });
        return parts.join("|");
    }
    var renderedSeq = [];
    var renderedSigs = [];
    var renderedNodes = [];
    var lastDateKey = null;
    var lastRole = null;
    function keyOf(m) {
        var info = m.info || m;
        return (info.id || "") + ":" + (info.role || "user") + ":" + (info.time && info.time.completed ? 1 : 0);
    }
    function msgContentSig(m) {
        var t = "";
        (m.parts || []).forEach(function(p) {
            if (p.type === "text" && p.text) {
                t += p.text;
            }
        });
        return t.length + ":" + hashString(t);
    }
    function appendMessageNode(m) {
        var info = m.info || m;
        var ts = info.time && (info.time.completed || info.time.created) || Date.now();
        var d = new Date(ts);
        var dayKey = d.toDateString();
        if (dayKey !== lastDateKey) {
            el.messages.appendChild(renderDateSeparator(d));
            lastDateKey = dayKey;
            lastRole = null;
        }
        var node = renderMessage(m);
        if (!node) {
            return;
        }
        var role = info.role;
        if (lastRole === role) {
            node.classList.add("grouped");
        }
        var key = keyOf(m);
        if (!renderedMsgKeys[key]) {
            node.classList.add("animate");
            renderedMsgKeys[key] = true;
        }
        lastRole = role;
        el.messages.appendChild(node);
        var mentioned = mentionFilesInText(msgPartsText(m));
        var post = mentioned.length ? buildUploadPostBubble(mentioned, false) : null;
        if (post) {
            el.messages.appendChild(post);
        }
        renderedSeq.push(key);
        renderedSigs.push(msgContentSig(m));
        renderedNodes.push({
            node: node,
            post: post
        });
    }
    function renderMessages(msgs) {
        var list = (msgs || []).filter(function(m) {
            var info = m.info || m;
            return info.role === "user" || info.role === "assistant";
        });
        var newKeys = list.map(keyOf);
        var common = 0;
        while (common < renderedSeq.length && common < newKeys.length && renderedSeq[common] === newKeys[common]) {
            common++;
        }
        var tailReplaced = renderedSeq.length === newKeys.length && common === renderedSeq.length - 1 && renderedSeq[renderedSeq.length - 1] !== newKeys[newKeys.length - 1];
        var structural = common < renderedSeq.length && !tailReplaced || !newKeys.length && (renderedSeq.length || el.messages.querySelector(".empty-state") === null) || newKeys.length && el.messages.querySelector(".empty-state");
        if (structural || !renderedSeq.length && newKeys.length) {
            el.messages.innerHTML = "";
            renderedSeq = [];
            renderedSigs = [];
            renderedNodes = [];
            lastDateKey = null;
            lastRole = null;
            if (!list.length) {
                var empty = document.createElement("div");
                empty.className = "empty-state";
                empty.innerHTML = '<svg class="emoji" viewBox="0 0 120 120" width="120" height="120" xmlns="http://www.w3.org/2000/svg">' + '<circle class="es-bg" cx="60" cy="60" r="55"/>' + '<rect x="22" y="38" width="60" height="46" rx="10" fill="white" stroke="var(--chat-text-meta)" stroke-width="2"/>' + '<path class="es-stroke" d="M30 50h44M30 60h32M30 70h28"/>' + '<circle class="es-accent" cx="78" cy="80" r="14"/>' + '<path class="es-stroke" stroke="white" stroke-width="2.5" d="M73 80l3 3 6-6"/>' + "</svg>" + '<h3 data-i18n="emptyTitle">' + esc(t("emptyTitle")) + "</h3>" + '<div data-i18n="emptyBody">' + esc(t("emptyBody")) + "</div>";
                el.messages.appendChild(empty);
            } else {
                list.forEach(appendMessageNode);
            }
        } else if (sameAsRendered(newKeys, list)) {} else if (common < newKeys.length) {
            list.slice(common).forEach(appendMessageNode);
        } else if (tailReplaced || msgContentSig(list[list.length - 1]) !== renderedSigs[renderedSigs.length - 1]) {
            var tail = renderedNodes[renderedNodes.length - 1];
            if (tail && tail.node.parentNode) {
                tail.node.parentNode.removeChild(tail.node);
            }
            if (tail && tail.post && tail.post.parentNode) {
                tail.post.parentNode.removeChild(tail.post);
            }
            renderedSeq.pop();
            renderedSigs.pop();
            renderedNodes.pop();
            appendMessageNode(list[list.length - 1]);
        }
        var qa = document.getElementById("question-answers");
        if (qa && qa.parentNode) {
            el.messages.appendChild(qa);
        }
        renderQueuedMessages(false);
        renderInlineQuestions(false);
        scrollToBottom();
    }
    function sameAsRendered(newKeys, list) {
        if (newKeys.length !== renderedSeq.length) {
            return false;
        }
        for (var i = 0; i < newKeys.length; i++) {
            if (newKeys[i] !== renderedSeq[i]) {
                return false;
            }
        }
        for (var i = 0; i < list.length; i++) {
            if (msgContentSig(list[i]) !== renderedSigs[i]) {
                return false;
            }
        }
        return true;
    }
    function renderDateSeparator(d) {
        var sep = document.createElement("div");
        sep.className = "date-separator";
        sep.textContent = formatDateLabel(d);
        return sep;
    }
    function appendMessage(role, text, opts) {
        opts = opts || {};
        var s = getCurrentSession();
        if (!s) return null;
        var m = {
            info: {
                id: makeMsgId(),
                role: role,
                time: {
                    created: Date.now(),
                    completed: Date.now()
                }
            },
            parts: [ {
                type: "text",
                text: text
            } ]
        };
        if (opts.parts) m.parts = opts.parts;
        s.messages.push(m);
        s.updatedAt = Date.now();
        if (role === "user" && s.title === I18N.t("sessionNew") && text) {
            var ptext = text.trim();
            if (ptext) s.title = ptext.slice(0, 35) + (ptext.length > 35 ? "…" : "");
        }
        saveMockSessions();
        var empty = el.messages.querySelector(".empty-state");
        if (empty) empty.remove();
        el.messages.appendChild(renderMessage(m));
        renderSessionList();
        scrollToBottom(true);
        return m;
    }
    function scrollToBottom(force) {
        var m = el.messages;
        var near = m.scrollHeight - m.scrollTop - m.clientHeight < 140;
        if (!force && !near) return;
        requestAnimationFrame(function() {
            m.scrollTop = m.scrollHeight;
        });
    }
    function markUserMessagesRead() {
        setTimeout(function() {
            var ticks = el.messages.querySelectorAll(".msg.user .ticks.delivered");
            for (var i = 0; i < ticks.length; i++) {
                ticks[i].classList.remove("delivered");
                ticks[i].classList.add("read");
                ticks[i].style.color = "var(--chat-accent)";
            }
        }, 800);
    }
    function updateSessionTitle() {
        el.sessionTitle.textContent = BOT_NAME;
    }
    function queueMessage(text) {
        state.queue.push({
            text: text,
            ts: Date.now()
        });
        renderQueuedMessages(true);
    }
    var lastQueueSig = null;
    var queuedMessagesNode = null;
    function renderQueuedMessages(scroll) {
        if (!queuedMessagesNode) {
            queuedMessagesNode = document.createElement("div");
            queuedMessagesNode.id = "queued-messages";
        }
        var host = queuedMessagesNode;
        if (host.parentNode) host.parentNode.removeChild(host);
        if (!state.queue.length) {
            lastQueueSig = null;
            return;
        }
        var sig = state.queue.map(function(q) {
            return q.text + ":" + q.ts;
        }).join("|");
        if (sig !== lastQueueSig) {
            lastQueueSig = sig;
            host.innerHTML = "";
            state.queue.forEach(function(q) {
                var wrap = document.createElement("div");
                wrap.className = "msg user queued animate";
                var bubble = document.createElement("div");
                bubble.className = "bubble";
                var textEl = document.createElement("div");
                textEl.textContent = q.text;
                bubble.appendChild(textEl);
                var meta = document.createElement("span");
                meta.className = "meta";
                var timeSpan = document.createElement("span");
                timeSpan.className = "time";
                timeSpan.textContent = formatTime(new Date(q.ts));
                meta.appendChild(timeSpan);
                var ticks = document.createElement("span");
                ticks.className = "ticks queued";
                ticks.title = "In der Warteschlange";
                ticks.innerHTML = '<svg viewBox="0 0 14 12" width="14" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' + '<polyline points="2 7 5.5 10.5 12 1"/>' + "</svg>";
                meta.appendChild(ticks);
                bubble.appendChild(meta);
                wrap.appendChild(bubble);
                host.appendChild(wrap);
            });
        }
        el.messages.appendChild(host);
        if (scroll) scrollToBottom(true);
    }
    function clearQueuedMessages() {
        state.queue = [];
        var host = document.getElementById("queued-messages");
        if (host && host.parentNode) host.parentNode.removeChild(host);
    }
    function processQueue() {
        if (!state.queue.length || state.generating) return;
        var next = state.queue.shift();
        renderQueuedMessages(false);
        setTimeout(function() {
            dispatchMessage(next.text);
        }, 250);
    }
    function getSessionMembers(s) {
        if (!s || !s.id) return [];
        var raw = storageGet(MEMBERS_KEY + s.id);
        if (raw) {
            try {
                var arr = JSON.parse(raw);
                if (Array.isArray(arr)) return arr;
            } catch (e) {}
        }
        return Array.isArray(s.members) ? s.members : [];
    }
    function addSessionMember(s, alias) {
        if (!s || !s.id) return;
        var members = getSessionMembers(s);
        if (members.indexOf(alias) !== -1) return;
        members.push(alias);
        storageSet(MEMBERS_KEY + s.id, JSON.stringify(members));
        if (state.backend === "api" && s.raw && s.raw.title && s.raw.title.indexOf("[" + alias + "]") === -1) {
            var nt = s.raw.title + " [" + alias + "]";
            s.raw.title = nt;
            s.title = displayTitle(nt);
            s.members = membersFromTitle(nt);
            renameSession(s.id, nt).then(function() {
                if (state.sessionId === s.id) {
                    refresh();
                } else {
                    renderSessionList();
                }
            });
        }
    }
    function buildSessionAvatars(s) {
        var wrap = document.createElement("div");
        wrap.className = "avatars";
        var mine = document.createElement("div");
        mine.className = "avatar gradient-" + (Math.abs(hashString(state.user ? state.user.alias : "me")) % 5 + 1);
        mine.textContent = state.user ? state.user.alias : "ich";
        wrap.appendChild(mine);
        var members = s && getSessionMembers(s) || [];
        var v = document.createElement("div");
        v.className = "avatar avatar-veronica";
        v.textContent = "V";
        wrap.appendChild(v);
        if (members.length > 0) {
            var more = document.createElement("div");
            more.className = "avatar avatar-more";
            more.textContent = "+" + members.length;
            wrap.appendChild(more);
        }
        return wrap;
    }
    function renderSessionList() {
        el.sessionList.innerHTML = "";
        var filter = (el.searchInput && el.searchInput.value || "").toLowerCase().trim();
        var seen = state.backend === "api" ? loadSeen() : null;
        state.sessions.forEach(function(s) {
            var title = s.title || t("sessionNew");
            if (filter && title.toLowerCase().indexOf(filter) === -1) return;
            var li = document.createElement("li");
            if (s.id === state.sessionId) li.classList.add("active");
            li.appendChild(buildSessionAvatars(s));
            var info = document.createElement("div");
            info.className = "info";
            var nameRow = document.createElement("div");
            nameRow.className = "name-row";
            var titleEl = document.createElement("div");
            titleEl.className = "title";
            titleEl.textContent = title;
            var time = document.createElement("span");
            time.className = "time";
            time.textContent = formatTimeShort(s.updatedAt || s.createdAt || Date.now());
            nameRow.appendChild(titleEl);
            nameRow.appendChild(time);
            var previewRow = document.createElement("div");
            previewRow.className = "preview-row";
            var preview = document.createElement("div");
            preview.className = "preview";
            var unread = 0;
            if (state.backend === "mock") {
                unread = s.unreadCount || 0;
                var lastMsg = s.messages && s.messages.length ? s.messages[s.messages.length - 1] : null;
                if (lastMsg) {
                    var role = lastMsg.info && lastMsg.info.role || "user";
                    var text = lastMsg.parts && lastMsg.parts[0] && lastMsg.parts[0].text || "";
                    var prefix = role === "user" ? "Du: " : "";
                    preview.textContent = prefix + String(text).slice(0, 60);
                    if (unread > 0 && s.id !== state.sessionId) preview.classList.add("unread");
                } else {
                    preview.textContent = t("noMessages");
                }
            } else {
                var seenAt = seen && seen[s.id] || 0;
                if (s.updatedAt > seenAt && s.id !== state.sessionId) unread = 1;
                preview.textContent = title;
                if (unread) preview.classList.add("unread");
            }
            previewRow.appendChild(preview);
            if (unread > 0 && s.id !== state.sessionId) {
                var badge = document.createElement("span");
                badge.className = "badge" + (state.backend === "api" ? " dot" : "");
                badge.textContent = state.backend === "api" ? "" : unread > 99 ? "99+" : unread;
                previewRow.appendChild(badge);
            }
            info.appendChild(nameRow);
            info.appendChild(previewRow);
            li.appendChild(info);
            li.addEventListener("click", function() {
                switchSession(s.id);
            });
            li.addEventListener("contextmenu", function(e) {
                e.preventDefault();
                appConfirm(t("chatDeleteAsk2"), {
                    title: t("chatDeleteTitle"),
                    danger: true,
                    okLabel: t("adminDelete")
                }).then(function(ok) {
                    if (ok) deleteSession(s.id);
                });
            });
            el.sessionList.appendChild(li);
        });
    }
    function mockResponses() {
        return {
            greeting: [ t("mockHello1"), t("mockHello2"), t("mockHello3") ],
            project: [ t("mockProject1"), t("mockAsk"), t("mockProject2") ],
            help: [ t("mockHelp"), t("mockHelp2") ],
            default: [ t("mockAck"), t("mockDefault2"), t("mockAsk2"), t("mockOkay") ]
        };
    }
    function pickResponse(text) {
        var tl = text.toLowerCase().trim();
        var mock = mockResponses();
        if (!tl) return random(mock.greeting);
        if (/^(hi|hallo|hey|moin|servus|guten|hello|hiya)\b/.test(tl)) return random(mock.greeting);
        if (/^(hilfe|help|was kannst|was geht)/.test(tl)) return random(mock.help);
        if (/projekt|datei|ordner|verzeichnis|struktur|project|file|folder|directory|structure/.test(tl)) return random(mock.project);
        if (/^(danke|thanks|thx)/.test(tl)) return t("mockThanks");
        if (/^(tschüss|tschuss|bye|ciao)/.test(tl)) return t("mockBye");
        return random(mock.default);
    }
    function random(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }
    function triggerAgentResponse(userText) {
        state.forcedIdle = false;
        state.stalePolls = 0;
        state.stuckShown = false;
        setTyping(true);
        updateComposerState();
        markUserMessagesRead();
        var delay = 900 + Math.random() * 1400;
        setTimeout(function() {
            if (!state.user || state.backend !== "mock") return;
            var reply = pickResponse(userText);
            setTyping(false);
            appendMessage("assistant", reply);
            state.generating = false;
            updateComposerState();
            scrollToBottom();
            setTimeout(processQueue, 400);
        }, delay);
    }
    function liveQuestions(msgs) {
        if (!msgs || !msgs.length) {
            return null;
        }
        var last = null;
        for (var i = msgs.length - 1; i >= 0; i--) {
            var info = msgs[i].info || msgs[i];
            if (info.role === "assistant") {
                last = msgs[i];
                break;
            }
        }
        if (!last) {
            return null;
        }
        var li = last.info || last;
        if (li.time && li.time.completed) {
            return null;
        }
        var parts = last.parts || [];
        var list = [];
        for (var j = 0; j < parts.length; j++) {
            var p = parts[j];
            if (p.type === "tool" && p.tool === "question" && p.state && p.state.status !== "completed" && p.state.status !== "error" && p.state.input) {
                var input = p.state.input;
                var questions = Array.isArray(input) ? input : input.questions || [];
                if (questions.length) {
                    list.push({
                        id: p.callID,
                        sessionID: li.sessionID,
                        questions: questions,
                        recovered: true
                    });
                }
            }
        }
        return list.length ? list : null;
    }
    function pendingCount() {
        var n = state.pendingPermissions.length;
        state.pendingQuestions.forEach(function(r) {
            n += Math.max(1, (r.questions || []).length);
        });
        return n;
    }
    var lastInlineQSig = null;
    var questionInlineNode = null;
    function currentInlineQuestion() {
        for (var i = 0; i < state.pendingQuestions.length; i++) {
            var r = state.pendingQuestions[i];
            var qs = r.questions || [];
            var answered = state.qAnsweredCount[r.id || r.requestID] || 0;
            if (qs.length > answered) {
                return {
                    req: r,
                    answered: answered,
                    q: qs[answered]
                };
            }
        }
        return null;
    }
    function renderInlineQuestions(scroll) {
        if (!questionInlineNode) {
            questionInlineNode = document.createElement("div");
            questionInlineNode.id = "question-inline";
        }
        var host = questionInlineNode;
        if (host.parentNode) host.parentNode.removeChild(host);
        var cur = currentInlineQuestion();
        if (!cur) {
            lastInlineQSig = null;
            return;
        }
        var reqId = cur.req.id || cur.req.requestID;
        var q = cur.q || {};
        var sig = JSON.stringify([ reqId, cur.answered, q.header, q.question, q.options, window.I18N.lang() ]);
        if (sig !== lastInlineQSig) {
            lastInlineQSig = sig;
            host.innerHTML = "";
            var wrap = document.createElement("div");
            wrap.className = "msg assistant animate";
            var bubble = document.createElement("div");
            bubble.className = "bubble question-bubble";
            if (q.header) {
                var head = document.createElement("div");
                head.className = "question-head";
                head.textContent = q.header;
                bubble.appendChild(head);
            }
            var qText = document.createElement("div");
            qText.className = "question-text";
            qText.textContent = q.question || t("question");
            bubble.appendChild(qText);
            if (cur.req.questions.length > 1) {
                var step = document.createElement("div");
                step.className = "question-step";
                step.textContent = t("questionStep", {
                    n: cur.answered + 1,
                    total: cur.req.questions.length
                });
                bubble.appendChild(step);
            }
            var opts = q.options || [];
            var inputWrap = document.createElement("div");
            inputWrap.className = "question-input-row";
            var input = document.createElement("input");
            input.type = "text";
            input.className = "question-input";
            input.placeholder = opts.length ? t("ownAnswerPh") : t("ownAnswer");
            var echoTextFor = function(vals) {
                return (q.header ? q.header + ": " : "") + (vals.join(", ") || "(keine Angabe)");
            };
            var submit = function(vals) {
                var all = state.qPendingAnswers[reqId] = state.qPendingAnswers[reqId] || [];
                all.push(vals);
                var nextIdx = cur.answered + 1;
                var isLast = nextIdx >= (cur.req.questions || []).length;
                if (!cur.req.recovered) appendAnswerEcho(echoTextFor(vals));
                if (!isLast) {
                    state.qAnsweredCount[reqId] = nextIdx;
                    lastInlineQSig = null;
                    renderInlineQuestions(true);
                    return;
                }
                state.qAnsweredCount[reqId] = nextIdx;
                lastInlineQSig = null;
                var payload = state.qPendingAnswers[reqId] || [];
                delete state.qPendingAnswers[reqId];
                answerQuestion(cur.req, payload);
            };
            if (opts.length) {
                var optRow = document.createElement("div");
                optRow.className = "question-opts";
                opts.forEach(function(o) {
                    var label = typeof o === "string" ? o : o.label || "";
                    var b = document.createElement("button");
                    b.type = "button";
                    b.className = "question-opt" + (o && o.description ? " has-desc" : "");
                    var lbl = document.createElement("span");
                    lbl.className = "opt-label";
                    lbl.textContent = label;
                    b.appendChild(lbl);
                    if (o && o.description) {
                        var d = document.createElement("span");
                        d.className = "opt-desc";
                        d.textContent = o.description;
                        b.appendChild(d);
                    }
                    b.addEventListener("click", function() {
                        submit([ label ]);
                    });
                    optRow.appendChild(b);
                });
                bubble.appendChild(optRow);
            }
            input.addEventListener("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    var v = input.value.trim();
                    if (v) {
                        submit([ v ]);
                    } else if (opts.length) {
                        submit([]);
                    }
                }
            });
            inputWrap.appendChild(input);
            bubble.appendChild(inputWrap);
            wrap.appendChild(bubble);
            host.appendChild(wrap);
            setTimeout(function() {
                if (input.parentNode) input.focus();
            }, 60);
        }
        el.messages.appendChild(host);
        if (scroll) scrollToBottom(true);
    }
    var questionAnswersNode = null;
    function appendAnswerEcho(text) {
        if (!questionAnswersNode) {
            questionAnswersNode = document.createElement("div");
            questionAnswersNode.id = "question-answers";
        }
        var host = questionAnswersNode;
        var wrap = document.createElement("div");
        wrap.className = "msg user animate";
        var bubble = document.createElement("div");
        bubble.className = "bubble";
        var textEl = document.createElement("div");
        textEl.textContent = text;
        bubble.appendChild(textEl);
        var meta = document.createElement("span");
        meta.className = "meta";
        var timeSpan = document.createElement("span");
        timeSpan.className = "time";
        timeSpan.textContent = formatTime(new Date);
        meta.appendChild(timeSpan);
        var ticks = document.createElement("span");
        ticks.className = "ticks delivered";
        ticks.innerHTML = '<svg viewBox="0 0 22 16" width="22" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' + '<polyline points="1 8 5 12 10 5"/>' + '<polyline points="7 8 11 12 21 1"/>' + "</svg>";
        meta.appendChild(ticks);
        bubble.appendChild(meta);
        wrap.appendChild(bubble);
        host.appendChild(wrap);
        var ug = document.getElementById("upload-groups");
        var qm = document.getElementById("queued-messages");
        var qi = document.getElementById("question-inline");
        if (qi) {
            el.messages.insertBefore(host, qi);
        } else if (qm) {
            el.messages.insertBefore(host, qm);
        } else if (ug) {
            el.messages.insertBefore(host, ug);
        } else {
            el.messages.appendChild(host);
        }
        scrollToBottom(true);
    }
    function renderPending() {
        var sig = JSON.stringify([ state.pendingQuestions, state.pendingPermissions, window.I18N.lang() ]);
        if (sig === state.lastPendingSig) {
            return;
        }
        state.lastPendingSig = sig;
        var body = el.questionModalBody;
        body.innerHTML = "";
        var permCount = (state.pendingPermissions || []).length;
        el.questionModalCount.textContent = permCount;
        el.questionModal.hidden = permCount === 0;
        if (state.replyError && permCount > 0) {
            var errBox = document.createElement("div");
            errBox.className = "pending-box";
            errBox.textContent = t("errPrefix") + state.replyError;
            body.appendChild(errBox);
        }
        state.pendingPermissions.forEach(function(p) {
            var box = document.createElement("div");
            box.className = "pending-box";
            var title = document.createElement("div");
            title.className = "q";
            title.textContent = t("permPrefix") + (p.title || p.type || t("permAction"));
            box.appendChild(title);
            var opts = document.createElement("div");
            opts.className = "opts";
            [ [ "once", t("permAllow") ], [ "always", t("permAlways") ], [ "reject", t("permReject") ] ].forEach(function(pair) {
                var b = document.createElement("button");
                b.type = "button";
                b.className = "btn " + (pair[0] === "reject" ? "btn-danger" : "btn-primary");
                b.textContent = pair[1];
                b.addEventListener("click", function() {
                    answerPermission(p, pair[0]);
                });
                opts.appendChild(b);
            });
            box.appendChild(opts);
            body.appendChild(box);
        });
        if (el.pending) {
            el.pending.innerHTML = "";
        }
        renderInlineQuestions(false);
    }
    function renderPlanToggle() {
        if (!el.planModeBtn) return;
        el.planModeBtn.classList.toggle("active", state.planMode);
        el.planModeBtn.setAttribute("aria-pressed", state.planMode ? "true" : "false");
    }
    function setPlan(on) {
        state.planMode = !!on;
        storageSet(PLAN_KEY, state.planMode ? "1" : "0");
        renderPlanToggle();
    }
    function togglePlanMode() {
        var wasOn = state.planMode;
        setPlan(!wasOn);
        appendNotice(!wasOn ? t("planOn") : t("planOff"));
        if (wasOn) {
            sendPlanGo();
        }
    }
    function sendPlanGo() {
        if (state.backend !== "api" || !state.sessionId) {
            return;
        }
        var text = (el.prompt.value || "").trim() || t("planGo");
        el.prompt.value = "";
        el.prompt.style.height = "auto";
        state.generating = true;
        updateComposerState();
        setTyping(true);
        var body = {
            parts: [ {
                type: "text",
                text: text
            } ]
        };
        var pref = preferredModel();
        if (pref) {
            body.model = {
                providerID: pref.providerID,
                modelID: pref.modelID
            };
            var goVariant = modelVariantFor(pref);
            if (goVariant) {
                body.variant = goVariant;
            }
        }
        if (window.CHAT_CONFIG && window.CHAT_CONFIG.agent) {
            body.agent = window.CHAT_CONFIG.agent;
        } else {
            body.agent = "veronica";
        }
        api("POST", "/session/" + state.sessionId + "/prompt_async", body).then(function() {
            return refresh();
        }).catch(function(e) {
            state.generating = false;
            updateComposerState();
            setTyping(false);
            appendNotice(t("errPrefix") + e.message);
        });
    }
    function answerQuestion(req, payload, onEcho) {
        var echoText = (payload || []).map(function(vals, i) {
            var qs = (req.questions || [])[i] || {};
            return (qs.header ? qs.header + ": " : "") + (vals.join(", ") || "(keine Angabe)");
        }).join("\n");
        if (onEcho) onEcho(echoText);
        var reply = api("POST", "/question/" + encodeURIComponent(req.id || req.requestID) + "/reply", {
            answers: payload
        });
        var done = function() {
            state.replyError = "";
            return refresh();
        };
        var fail = function(e) {
            state.replyError = "Antwort konnte nicht gesendet werden: " + e.message;
            renderPending();
        };
        if (req.recovered) {
            var sid = state.sessionId;
            var text = payload.map(function(vals, i) {
                var qs = (req.questions || [])[i] || {};
                return (qs.header ? qs.header + ": " : "") + (vals.join(", ") || "(keine Angabe)");
            }).join("\n");
            api("POST", "/session/" + sid + "/prompt_async", {
                parts: [ {
                    type: "text",
                    text: text
                } ]
            }).then(done).catch(fail);
            return;
        }
        reply.then(done).catch(fail);
    }
    function answerPermission(p, reply) {
        api("POST", "/permission/" + encodeURIComponent(p.id || p.permissionID) + "/reply", {
            reply: reply
        }).then(function() {
            state.replyError = "";
            return refresh();
        }).catch(function(e) {
            state.replyError = "Freigabe konnte nicht gesendet werden: " + e.message;
            renderPending();
        });
    }
    function hideStuck() {
        state.stuckShown = false;
        if (el.stuck) {
            el.stuck.innerHTML = "";
        }
    }
    function showStuck() {
        state.stuckShown = true;
        if (!el.stuck) {
            return;
        }
        el.stuck.innerHTML = "";
        var box = document.createElement("div");
        box.className = "pending-box";
        var head = document.createElement("div");
        head.className = "q";
        head.textContent = t("stuckHead");
        box.appendChild(head);
        var opts = document.createElement("div");
        opts.className = "opts";
        var cont = document.createElement("button");
        cont.className = "btn btn-primary";
        cont.textContent = t("stuckUnlock");
        cont.addEventListener("click", function() {
            state.forcedIdle = true;
            state.generating = false;
            updateComposerState();
            setTyping(false);
            hideStuck();
            refresh();
        });
        opts.appendChild(cont);
        var dismiss = document.createElement("button");
        dismiss.className = "btn";
        dismiss.textContent = t("stuckWait");
        dismiss.addEventListener("click", function() {
            state.stalePolls = 0;
            hideStuck();
        });
        opts.appendChild(dismiss);
        box.appendChild(opts);
        el.stuck.appendChild(box);
    }
    function trackGeneration(msgs) {
        if (!state.generating) {
            state.stalePolls = 0;
            state.lastGenSig = null;
            hideStuck();
            return;
        }
        var sig = JSON.stringify(msgs || []);
        if (sig !== state.lastGenSig) {
            state.lastGenSig = sig;
            state.stalePolls = 0;
            return;
        }
        state.stalePolls++;
        if (state.stalePolls >= STUCK_POLLS && !state.stuckShown && !state.pendingQuestions.length && !state.pendingPermissions.length) {
            showStuck();
        }
    }
    function getMessages(sessionId) {
        if (state.backend === "api") {
            return api("GET", "/session/" + encodeURIComponent(sessionId) + "/message");
        }
        var s = findSession(sessionId);
        return Promise.resolve(s && s.messages || []);
    }
    function updateGeneratingFromMsgs(msgs) {
        var wasGenerating = state.generating;
        var gen = !state.forcedIdle && (msgs || []).some(function(m) {
            var info = m.info || m;
            return info.role === "assistant" && !(info.time && info.time.completed);
        });
        state.generating = gen;
        if (wasGenerating && !gen) {
            setTimeout(processQueue, 400);
        }
    }
    function refresh() {
        if (!state.user) {
            return Promise.resolve();
        }
        var jobs = [ loadSessions() ];
        if (state.sessionId) {
            jobs.push(getMessages(state.sessionId).then(function(msgs) {
                state.lastMsgs = msgs || [];
                updateGeneratingFromMsgs(msgs);
                trackGeneration(msgs);
                var sig = visibleSig(msgs);
                if (sig !== state.lastRendered) {
                    renderMessages(msgs);
                    state.lastRendered = sig;
                    markSeen(state.sessionId);
                }
                updateComposerState();
                setTyping(state.generating);
            }));
            jobs.push(api("GET", "/question").then(function(qs) {
                var live = (qs || []).filter(function(q) {
                    return !q.sessionID || q.sessionID === state.sessionId;
                });
                state.pendingQuestions = live.length ? live : liveQuestions(state.lastMsgs) || [];
                renderPending();
            }).catch(function() {
                state.pendingQuestions = liveQuestions(state.lastMsgs) || [];
                renderPending();
            }));
            jobs.push(api("GET", "/permission").then(function(ps) {
                var list = Array.isArray(ps) ? ps : ps && ps.permissions || [];
                state.pendingPermissions = list.filter(function(p) {
                    return !p.sessionID || p.sessionID === state.sessionId;
                });
                renderPending();
            }).catch(function() {
                state.pendingPermissions = [];
                renderPending();
            }));
        }
        return Promise.all(jobs.map(function(p) {
            return p.catch(function() {});
        }));
    }
    var VOICE_ENABLED = false;
    function updateComposerState() {
        var hasText = el.prompt.value.trim().length > 0;
        if (state.recording) {
            el.micBtn.hidden = true;
            el.sendBtn.hidden = true;
            el.stopBtn.hidden = true;
            el.attachBtn.disabled = true;
            el.prompt.disabled = true;
        } else {
            el.micBtn.hidden = !VOICE_ENABLED || hasText;
            el.sendBtn.hidden = !hasText;
            el.stopBtn.hidden = !state.generating || hasText;
            el.attachBtn.disabled = false;
            el.prompt.disabled = false;
        }
    }
    function setTyping(on) {
        el.typingIndicator.hidden = !on;
        el.statusText.textContent = on ? t("statusTyping") : t("statusOnline");
        el.statusText.classList.toggle("typing", !!on);
        if (on) scrollToBottom();
    }
    function preferredModel() {
        var saved = storageGet(MODEL_KEY);
        if (saved) {
            var i = saved.indexOf("/");
            if (i > 0) {
                if (Object.keys(knownModels).length && !knownModels[saved]) {
                    storageDel(MODEL_KEY);
                    appendNotice(t("modelMissing").replace("{model}", saved));
                    return null;
                }
                return {
                    providerID: saved.slice(0, i),
                    modelID: saved.slice(i + 1)
                };
            }
        }
        if (window.CHAT_CONFIG && window.CHAT_CONFIG.provider && window.CHAT_CONFIG.model) {
            return {
                providerID: window.CHAT_CONFIG.provider,
                modelID: window.CHAT_CONFIG.model
            };
        }
        return null;
    }
    var knownModels = {};
    var modelGroups = [];
    var modelVariants = {};
    var modelMenuLoading = false;
    function modelVariantFor(pref) {
        if (!pref) {
            return null;
        }
        return modelVariants[pref.providerID + "/" + pref.modelID] || null;
    }
    function activeModelKey() {
        var saved = storageGet(MODEL_KEY);
        if (saved) return saved;
        if (window.CHAT_CONFIG && window.CHAT_CONFIG.provider && window.CHAT_CONFIG.model) {
            return window.CHAT_CONFIG.provider + "/" + window.CHAT_CONFIG.model;
        }
        return modelGroups[0] && modelGroups[0].keys[0] ? modelGroups[0].keys[0].key : "";
    }
    function activeModelLabel() {
        var active = activeModelKey();
        for (var g = 0; g < modelGroups.length; g++) {
            for (var i = 0; i < modelGroups[g].keys.length; i++) {
                if (modelGroups[g].keys[i].key === active) {
                    return modelGroups[g].keys[i].label;
                }
            }
        }
        if (active) {
            var i2 = active.indexOf("/");
            return i2 > 0 ? active.slice(i2 + 1) : active;
        }
        return "";
    }
    function updateModelBadge() {
        if (!el.modelBadge) {
            return;
        }
        var label = activeModelLabel();
        el.modelBadge.textContent = label;
        el.modelBadge.hidden = !label;
        el.modelMenuBtn.title = label ? t("modelTitle") + ": " + label : t("modelTitle");
    }
    function renderModelMenu() {
        if (!el.chatModelItems) return;
        el.chatModelItems.innerHTML = "";
        var active = activeModelKey();
        updateModelBadge();
        var groups = modelGroups;
        if (!groups.length) {
            var info = document.createElement("div");
            info.className = "chat-menu-item model-empty";
            info.textContent = t("modelsNone");
            el.chatModelItems.appendChild(info);
            return;
        }
        groups.forEach(function(g) {
            if (g.label) {
                var gl = document.createElement("div");
                gl.className = "model-group-label";
                gl.textContent = g.label;
                el.chatModelItems.appendChild(gl);
            }
            g.keys.forEach(function(m) {
                var b = document.createElement("button");
                b.type = "button";
                b.className = "chat-menu-item model-item" + (m.key === active ? " active" : "");
                b.setAttribute("data-model", m.key);
                var label = document.createElement("span");
                label.className = "mi-label";
                label.textContent = m.label;
                label.title = m.label + " (" + m.prov + ")";
                var prov = document.createElement("em");
                prov.className = "mi-prov";
                prov.textContent = m.prov;
                b.appendChild(label);
                b.appendChild(prov);
                b.addEventListener("click", function(e) {
                    e.stopPropagation();
                    storageSet(MODEL_KEY, m.key);
                    renderModelMenu();
                });
                el.chatModelItems.appendChild(b);
            });
        });
    }
    function loadModelsForMenu(attempt) {
        if (modelMenuLoading || state.backend !== "api") {
            renderModelMenu();
            return;
        }
        modelMenuLoading = true;
        var tryN = attempt || 0;
        api("GET", "/config/providers", null, 2e4).then(function(res) {
            var providers = res && res.providers || [];
            if (!providers.length) {
                throw new Error("no providers");
            }
            var defaults = res && res.default || {};
            var known = {};
            providers.forEach(function(p) {
                var models = p.models || {};
                Object.keys(models).forEach(function(mid) {
                    var m = models[mid] || {};
                    known[p.id + "/" + (m.id || mid)] = {
                        key: p.id + "/" + (m.id || mid),
                        label: m.name || m.id || mid,
                        prov: p.name || p.id,
                        def: defaults[p.id] === (m.id || mid),
                        zen: p.id === "opencode"
                    };
                });
            });
            knownModels = known;
            var stateKeys = function(list) {
                return (Array.isArray(list) ? list : []).map(function(x) {
                    return x && x.providerID && x.modelID ? x.providerID + "/" + x.modelID : null;
                }).filter(function(k, i, a) {
                    return k && known[k] && a.indexOf(k) === i;
                });
            };
            return fetch(location.origin + BASE + "models.json", {
                cache: "no-store"
            }).then(function(r) {
                return r.ok ? r.json() : null;
            }).catch(function() {
                return null;
            }).then(function(local) {
                modelVariants = local && local.variant || {};
                var fav = stateKeys(local && local.favorite);
                var rec = stateKeys(local && local.recent);
                var groups = [];
                if (fav.length) {
                    groups.push({
                        label: t("modelsFav"),
                        keys: fav.map(function(k) {
                            return known[k];
                        })
                    });
                } else if (rec.length) {
                    groups.push({
                        label: t("modelsRecent"),
                        keys: rec.map(function(k) {
                            return known[k];
                        })
                    });
                } else {
                    var zen = Object.keys(known).filter(function(k) {
                        return known[k].zen;
                    });
                    if (zen.length) {
                        groups.push({
                            label: t("modelsRec"),
                            keys: zen.map(function(k) {
                                return known[k];
                            })
                        });
                    }
                    var rest = Object.keys(known).filter(function(k) {
                        return !known[k].zen;
                    });
                    if (rest.length) {
                        groups.push({
                            label: t("modelsAll"),
                            keys: rest.map(function(k) {
                                return known[k];
                            })
                        });
                    }
                    if (!groups.length) {
                        groups.push({
                            label: t("modelsAll"),
                            keys: Object.keys(known).map(function(k) {
                                return known[k];
                            })
                        });
                    }
                }
                modelGroups = groups;
                renderModelMenu();
            });
        }).catch(function() {
            if (tryN < 2) {
                modelMenuLoading = false;
                return new Promise(function(r) {
                    setTimeout(r, 2500);
                }).then(function() {
                    loadModelsForMenu(tryN + 1);
                });
            }
            renderModelMenu();
        }).then(function() {
            modelMenuLoading = false;
        });
    }
    function findCommand(text) {
        if (!text || text.charAt(0) !== "/") {
            return null;
        }
        var m = text.match(/^\/([A-Za-z0-9_-]+)(?:\s+([\s\S]*))?$/);
        if (!m) {
            return null;
        }
        for (var i = 0; i < state.commands.length; i++) {
            if (state.commands[i].name === m[1]) {
                return {
                    command: state.commands[i],
                    args: (m[2] || "").trim()
                };
            }
        }
        return null;
    }
    function handleCommand(found) {
        var name = found.command.name;
        if (name === "new") {
            newChat();
            return;
        }
        if (name === "clear") {
            clearCurrent();
            return;
        }
        if (name === "help") {
            helpReply();
            return;
        }
        if (state.backend === "api" && found.command._real) {
            runCommand(found);
        }
    }
    function clearCurrent() {
        if (state.backend === "mock") {
            var s = getCurrentSession();
            if (s) {
                s.messages = [];
                s.updatedAt = Date.now();
                saveMockSessions();
                state.lastMsgs = [];
                state.lastRendered = null;
                renderMessages([]);
                renderSessionList();
                appendNotice(t("noticeCleared"));
            }
            return;
        }
        newChat();
        appendNotice(t("noticeNew"));
    }
    function appendNotice(text) {
        var notice = document.createElement("div");
        notice.className = "date-separator";
        notice.textContent = text;
        el.messages.appendChild(notice);
        scrollToBottom(true);
    }
    function helpReply() {
        if (state.backend === "mock") {
            var empty = el.messages.querySelector(".empty-state");
            if (empty) empty.remove();
            var m = {
                info: {
                    id: makeMsgId(),
                    role: "assistant",
                    time: {
                        created: Date.now(),
                        completed: Date.now()
                    }
                },
                parts: [ {
                    type: "text",
                    text: random(mockResponses().help)
                } ]
            };
            var s = getCurrentSession();
            if (!s) {
                createSession(t("helpSessionTitle")).then(function() {
                    s = getCurrentSession();
                    s.messages.push(m);
                    saveMockSessions();
                    renderSessionList();
                    el.messages.appendChild(renderMessage(m));
                    scrollToBottom(true);
                });
                return;
            }
            s.messages.push(m);
            s.updatedAt = Date.now();
            saveMockSessions();
            el.messages.appendChild(renderMessage(m));
            renderSessionList();
            scrollToBottom(true);
        } else {
            appendNotice(t("noticeCmds"));
        }
    }
    function runCommand(found) {
        var cmd = found.command;
        var body = {
            command: cmd.name,
            arguments: expandMentions(found.args)
        };
        if (window.CHAT_CONFIG && window.CHAT_CONFIG.agent) {
            body.agent = window.CHAT_CONFIG.agent;
        } else {
            body.agent = "veronica";
        }
        var cmdModel = preferredModel();
        if (cmdModel) {
            body.model = cmdModel.providerID + "/" + cmdModel.modelID;
        }
        var ensure = state.sessionId ? Promise.resolve() : createSession("/" + cmd.name);
        ensure.then(function() {
            el.prompt.value = "";
            el.prompt.style.height = "auto";
            state.forcedIdle = false;
            state.stalePolls = 0;
            state.lastGenSig = null;
            state.generating = true;
            updateComposerState();
            setTyping(true);
            return api("POST", "/session/" + state.sessionId + "/command", body).then(function() {
                return refresh();
            });
        }).catch(function(e) {
            state.generating = false;
            updateComposerState();
            setTyping(false);
            if (el.pending) {
                el.pending.innerHTML = '<div class="pending-box"><div class="q">' + esc(t("cmdErr")) + esc(e.message) + "</div></div>";
            }
        });
    }
    function send(text) {
        text = (text || "").trim();
        if (!text || !state.user) {
            return;
        }
        if (state.generating) {
            queueMessage(text);
            return;
        }
        dispatchMessage(text);
    }
    function dispatchMessage(text) {
        var found = findCommand(text);
        if (found) {
            handleCommand(found);
            return;
        }
        if (state.backend === "api") {
            text = expandMentions(text);
        }
        var displayTitle = text.slice(0, 40) + (text.length > 40 ? "…" : "");
        var freshSession = !state.sessionId;
        var ensure = state.sessionId ? Promise.resolve(getCurrentSession()) : createSession(displayTitle);
        ensure.then(function() {
            if (freshSession) {
                setPlan(true);
            }
            el.prompt.value = "";
            el.prompt.style.height = "auto";
            state.forcedIdle = false;
            state.stalePolls = 0;
            state.lastGenSig = null;
            state.generating = true;
            updateComposerState();
            setTyping(true);
            if (state.backend === "api") {
                var body = {
                    parts: [ {
                        type: "text",
                        text: text
                    } ]
                };
                var pref = preferredModel();
                if (pref) {
                    body.model = {
                        providerID: pref.providerID,
                        modelID: pref.modelID
                    };
                    var prefVariant = modelVariantFor(pref);
                    if (prefVariant) {
                        body.variant = prefVariant;
                    }
                }
                if (state.planMode) {
                    body.agent = "veronica-plan";
                } else if (window.CHAT_CONFIG && window.CHAT_CONFIG.agent) {
                    body.agent = window.CHAT_CONFIG.agent;
                } else {
                    body.agent = "veronica";
                }
                return api("POST", "/session/" + state.sessionId + "/prompt_async", body).then(function() {
                    return refresh();
                });
            }
            markUserMessagesRead();
            appendMessage("user", text);
            triggerAgentResponse(text);
        }).catch(function(e) {
            state.generating = false;
            updateComposerState();
            setTyping(false);
            if (el.pending) {
                el.pending.innerHTML = '<div class="pending-box"><div class="q">' + esc(t("errPrefix")) + esc(e.message) + "</div>" + t("providerHint") + "</div>";
            }
        });
    }
    function stopGeneration() {
        clearQueuedMessages();
        state.forcedIdle = true;
        state.generating = false;
        setTyping(false);
        updateComposerState();
        if (state.backend === "api" && state.sessionId) {
            api("POST", "/session/" + state.sessionId + "/abort", {}).then(refresh).catch(function() {});
        }
    }
    function loadIncomingApi() {
        return api("GET", "/upload/list").then(function(res) {
            state.incomingCache = res && res.files || [];
            state.incomingLoaded = true;
        }).catch(function() {});
    }
    function loadIncomingMock() {
        state.incomingCache = [ {
            name: "readme.md",
            size: 2048
        }, {
            name: "package.json",
            size: 512
        }, {
            name: "src/index.ts",
            size: 8192
        }, {
            name: "src/app.js",
            size: 16384
        }, {
            name: "docker-compose.yml",
            size: 1024
        } ];
        state.incomingLoaded = true;
    }
    function formatSize(bytes) {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(1) + " MB";
        }
        if (bytes >= 1024) {
            return Math.round(bytes / 1024) + " KB";
        }
        return bytes + " B";
    }
    function expandMentions(text) {
        if (!state.incomingCache.length) {
            return text;
        }
        return String(text).replace(/(^|\s)@([^\s@]+)/g, function(whole, pre, name) {
            if (name.toLowerCase().indexOf("incoming/") === 0) {
                return whole;
            }
            var found = state.incomingCache.some(function(f) {
                return f.name.toLowerCase() === name.toLowerCase();
            });
            return found ? pre + "@incoming/" + name : whole;
        });
    }
    var UPLOAD_TYPE_ORDER = [ "image", "video", "audio", "pdf", "doc", "sheet", "archive", "other" ];
    function uploadTypeLabel(key) {
        var map = {
            image: "typeImage",
            video: "typeVideo",
            audio: "typeAudio",
            pdf: "typePdf",
            doc: "typeDoc",
            sheet: "typeSheet",
            archive: "typeArchive",
            other: "typeOther"
        };
        return t(map[key] || "typeOther");
    }
    function fileTypeMeta(name) {
        var ext = String(name || "").split(".").pop().toLowerCase();
        var key = "other";
        if (/^(png|jpe?g|gif|webp|bmp|svg|heic|avif)$/.test(ext)) key = "image"; else if (/^(mp4|mov|webm|avi|mkv)$/.test(ext)) key = "video"; else if (/^(mp3|wav|ogg|m4a|flac|aac)$/.test(ext)) key = "audio"; else if (ext === "pdf") key = "pdf"; else if (/^(doc|docx|odt|rtf|txt|md)$/.test(ext)) key = "doc"; else if (/^(xls|xlsx|ods|csv)$/.test(ext)) key = "sheet"; else if (/^(zip|rar|7z|tar|gz|bz2)$/.test(ext)) key = "archive";
        var colors = {
            image: "#7c3aed",
            video: "#dc2626",
            audio: "#0d9488",
            pdf: "#ef4444",
            doc: "#2563eb",
            sheet: "#16a34a",
            archive: "#d97706",
            other: "#64748b"
        };
        return {
            key: key,
            label: uploadTypeLabel(key),
            color: colors[key],
            ext: (ext || "file").slice(0, 4).toUpperCase()
        };
    }
    function formatBytes(n) {
        if (typeof n !== "number" || isNaN(n)) return "";
        var units = [ "B", "KB", "MB", "GB" ];
        var i = 0;
        while (n >= 1024 && i < units.length - 1) {
            n /= 1024;
            i++;
        }
        return (i === 0 ? n : n.toFixed(1).replace(".", ",")) + " " + units[i];
    }
    function buildUploadCard(file) {
        var meta = fileTypeMeta(file.name);
        var card = document.createElement("div");
        card.className = "upload-card";
        card.innerHTML = '<span class="file-badge" style="background:' + meta.color + '">' + esc(meta.ext) + "</span>" + '<div class="file-info">' + '<div class="file-name">' + esc(file.name) + "</div>" + '<div class="file-meta">' + '<span class="file-size">' + esc(formatBytes(file.size)) + "</span>" + '<span class="progress"><span class="progress-bar"></span></span>' + "</div>" + "</div>" + '<span class="file-check"></span>';
        return {
            card: card,
            setProgress: function(pct) {
                var bar = card.querySelector(".progress-bar");
                if (bar) bar.style.width = Math.max(0, Math.min(100, pct)) + "%";
            },
            markDone: function() {
                card.classList.add("done");
                var chk = card.querySelector(".file-check");
                if (chk) chk.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
            },
            markError: function() {
                card.classList.add("error");
                var chk = card.querySelector(".file-check");
                if (chk) chk.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M6 18L18 6"/></svg>';
            },
            setNote: function(txt) {
                var m = card.querySelector(".file-meta");
                if (m) m.innerHTML = '<span class="file-note">' + esc(txt) + "</span>";
            }
        };
    }
    function uploadFileApi(file, onProgress) {
        return new Promise(function(resolve) {
            var fd = new FormData;
            fd.append("file", file);
            var xhr = new XMLHttpRequest;
            xhr.open("POST", API_BASE + "/upload");
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable && onProgress) {
                    onProgress(Math.round(e.loaded / e.total * 100));
                }
            };
            xhr.onload = function() {
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch (e) {
                    resolve({
                        ok: false
                    });
                }
            };
            xhr.onerror = function() {
                resolve({
                    ok: false
                });
            };
            xhr.send(fd);
        });
    }
    function uploadFiles(fileList) {
        if (!state.user) return;
        var list = Array.prototype.slice.call(fileList || []);
        if (!list.length) {
            return;
        }
        var uploaded = [];
        var buckets = {};
        list.forEach(function(f) {
            var meta = fileTypeMeta(f.name);
            if (!buckets[meta.key]) buckets[meta.key] = [];
            buckets[meta.key].push(f);
        });
        var groupKeys = Object.keys(buckets).sort(function(a, b) {
            return UPLOAD_TYPE_ORDER.indexOf(a) - UPLOAD_TYPE_ORDER.indexOf(b);
        });
        el.uploadStatus.hidden = false;
        el.uploadStatus.innerHTML = "";
        var jobs = [];
        groupKeys.forEach(function(key) {
            if (groupKeys.length > 1) {
                var head = document.createElement("div");
                head.className = "upload-group-label";
                head.textContent = uploadTypeLabel(key);
                el.uploadStatus.appendChild(head);
            }
            buckets[key].forEach(function(f) {
                var card = buildUploadCard(f);
                el.uploadStatus.appendChild(card.card);
                jobs.push({
                    file: f,
                    card: card
                });
            });
        });
        (function next(i) {
            if (i >= jobs.length) {
                if (state.backend === "api") loadIncomingApi();
                if (uploaded.length) {
                    recordSessionUploads(uploaded);
                }
                setTimeout(function() {
                    el.uploadStatus.hidden = true;
                    el.uploadStatus.innerHTML = "";
                }, 900);
                return;
            }
            var job = jobs[i];
            uploadFileApi(job.file, function(pct) {
                job.card.setProgress(pct);
            }).then(function(res) {
                if (res && res.ok && res.files && res.files[0] && res.files[0].ok) {
                    job.card.setProgress(100);
                    job.card.markDone();
                    uploaded.push({
                        name: res.files[0].name,
                        size: job.file.size,
                        key: fileTypeMeta(job.file.name).key,
                        ts: Date.now()
                    });
                    if (state.backend === "api") insertAtCursor("@" + res.files[0].name + " ");
                } else {
                    job.card.markError();
                    job.card.setNote("Upload fehlgeschlagen");
                }
                setTimeout(function() {
                    next(i + 1);
                }, 200);
            });
        })(0);
    }
    function getSessionUploads() {
        var sid = state.sessionId;
        if (!sid) return [];
        var raw = storageGet(UPLOADS_KEY + sid);
        if (!raw) return [];
        try {
            var arr = JSON.parse(raw);
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return [];
        }
    }
    function recordSessionUploads(files) {
        var sid = state.sessionId || "__pending";
        if (!files || !files.length) return;
        var raw = storageGet(UPLOADS_KEY + sid);
        var all = [];
        if (raw) {
            try {
                all = JSON.parse(raw) || [];
            } catch (e) {
                all = [];
            }
        }
        storageSet(UPLOADS_KEY + sid, JSON.stringify(all.concat(files)));
    }
    function ensurePendingUploads() {
        var sid = state.sessionId;
        if (!sid) return;
        var raw = storageGet(UPLOADS_KEY + "__pending");
        if (!raw) return;
        storageDel(UPLOADS_KEY + "__pending");
        var pending = [];
        try {
            pending = JSON.parse(raw) || [];
        } catch (e) {
            pending = [];
        }
        if (pending.length) {
            recordSessionUploads(pending);
        }
    }
    function uploadFileUrl(name) {
        return API_BASE + "/upload/file?name=" + encodeURIComponent(name);
    }
    var lastUploadsSig = null;
    function sanitizeFileId(name) {
        return String(name).replace(/[^A-Za-z0-9]/g, "_");
    }
    function uploadNameMap() {
        var names = {};
        getSessionUploads().forEach(function(u) {
            names[String(u.name).toLowerCase()] = u;
        });
        return names;
    }
    function msgPartsText(m) {
        var t = "";
        (m && m.parts || []).forEach(function(p) {
            if (p.type === "text" && p.text) {
                t += p.text;
            }
        });
        return t;
    }
    function mentionFilesInText(text) {
        if (!text || String(text).indexOf("@") === -1) {
            return [];
        }
        var names = uploadNameMap();
        var out = [];
        var seen = {};
        String(text).replace(/(^|[\s(])@((?:incoming\/)?[A-Za-z0-9._\-]+)/g, function(full, pre, raw) {
            var name = String(raw).replace(/^incoming\//i, "");
            var u = names[name.toLowerCase()];
            if (u && !seen[u.name]) {
                seen[u.name] = 1;
                out.push(u);
            }
            return full;
        });
        return out;
    }
    function buildUploadPostBubble(files, withIds) {
        var buckets = {};
        (files || []).forEach(function(u) {
            (buckets[u.key] = buckets[u.key] || []).push(u);
        });
        var host = document.createElement("div");
        host.className = "upload-post";
        Object.keys(buckets).sort(function(a, b) {
            return UPLOAD_TYPE_ORDER.indexOf(a) - UPLOAD_TYPE_ORDER.indexOf(b);
        }).forEach(function(key) {
            var bucket = buckets[key];
            var wrap = document.createElement("div");
            wrap.className = "msg user animate";
            var bubble = document.createElement("div");
            bubble.className = "bubble upload-msg-bubble";
            var label = document.createElement("div");
            label.className = "upload-msg-label";
            label.textContent = uploadTypeLabel(key) + " (" + bucket.length + ")";
            bubble.appendChild(label);
            bucket.forEach(function(u) {
                var card = document.createElement("a");
                card.className = "upload-msg-card";
                card.href = uploadFileUrl(u.name);
                card.target = "_blank";
                card.rel = "noopener";
                card.setAttribute("data-file", u.name);
                if (withIds) {
                    card.id = "upload-file-" + sanitizeFileId(u.name);
                }
                if (key === "image") {
                    var img = document.createElement("img");
                    img.className = "upload-msg-preview";
                    img.src = uploadFileUrl(u.name);
                    img.alt = u.name;
                    img.loading = "lazy";
                    card.appendChild(img);
                } else {
                    var badge = document.createElement("span");
                    badge.className = "file-badge";
                    badge.style.background = fileTypeMeta(u.name).color;
                    badge.textContent = fileTypeMeta(u.name).ext;
                    card.appendChild(badge);
                }
                var info = document.createElement("span");
                info.className = "file-info";
                var nameEl = document.createElement("span");
                nameEl.className = "file-name";
                nameEl.textContent = u.name;
                var sizeEl = document.createElement("span");
                sizeEl.className = "file-size";
                sizeEl.textContent = formatBytes(u.size) + " · öffnen";
                info.appendChild(nameEl);
                info.appendChild(sizeEl);
                card.appendChild(info);
                bubble.appendChild(card);
            });
            wrap.appendChild(bubble);
            host.appendChild(wrap);
        });
        return host;
    }
    function renderUploadGroups(scroll) {
        var host = document.getElementById("upload-groups");
        if (host && host.parentNode) {
            host.parentNode.removeChild(host);
        }
    }
    function linkifyMentions(bubble) {
        var uploads = getSessionUploads();
        if (!uploads.length) {
            return;
        }
        var names = {};
        uploads.forEach(function(u) {
            names[String(u.name).toLowerCase()] = String(u.name);
        });
        var walker = document.createTreeWalker(bubble, NodeFilter.SHOW_TEXT, null, false);
        var hits = [];
        var node;
        while (node = walker.nextNode()) {
            if ((node.nodeValue || "").indexOf("@") !== -1) {
                hits.push(node);
            }
        }
        hits.forEach(function(textNode) {
            var replaced = false;
            var html = esc(textNode.nodeValue).replace(/(^|[\s(])@((?:incoming\/)?[A-Za-z0-9._\-]+)/g, function(full, pre, rawName) {
                var name = String(rawName).replace(/^incoming\//i, "");
                var actual = names[name.toLowerCase()];
                if (!actual) {
                    return full;
                }
                replaced = true;
                return pre + '<a href="' + esc(uploadFileUrl(actual)) + '" class="file-mention" data-file="' + esc(actual) + '">@' + esc(rawName) + "</a>";
            });
            if (replaced) {
                var span = document.createElement("span");
                span.innerHTML = html;
                textNode.parentNode.replaceChild(span, textNode);
            }
        });
    }
    function jumpToUpload(name, sourceMsg) {
        var sel = '.upload-msg-card[data-file="' + String(name).replace(/"/g, "") + '"]';
        var card = null;
        if (sourceMsg) {
            var sib = sourceMsg.nextElementSibling;
            if (sib && sib.classList && sib.classList.contains("upload-post")) {
                card = sib.querySelector(sel);
            }
        }
        if (!card) {
            card = document.querySelector(sel);
        }
        if (!card) {
            window.open(uploadFileUrl(name), "_blank", "noopener");
            return;
        }
        card.scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
        card.classList.remove("shake");
        void card.offsetWidth;
        card.classList.add("shake");
        setTimeout(function() {
            card.classList.remove("shake");
        }, 700);
    }
    function insertAtCursor(text) {
        var pos = el.prompt.selectionStart || 0;
        var v = el.prompt.value;
        el.prompt.value = v.slice(0, pos) + text + v.slice(pos);
        var np = pos + text.length;
        el.prompt.setSelectionRange(np, np);
        el.prompt.focus();
        updateComposerState();
    }
    var atState = {
        items: [],
        index: 0,
        match: null
    };
    function updateAtPopup() {
        if (!state.incomingLoaded || !state.incomingCache.length) {
            el.atPopup.hidden = true;
            return;
        }
        var pos = el.prompt.selectionStart || 0;
        var before = el.prompt.value.slice(0, pos);
        var m = before.match(/(^|\s)@([^\s@]*)$/);
        if (!m) {
            el.atPopup.hidden = true;
            return;
        }
        var q = m[2].toLowerCase();
        atState.match = {
            start: pos - m[2].length - 1,
            end: pos
        };
        var items = state.incomingCache.filter(function(f) {
            return String(f.name).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 10);
        atState.items = items;
        atState.index = 0;
        el.atPopup.innerHTML = "";
        items.forEach(function(f, i) {
            var row = document.createElement("div");
            row.className = "at-item" + (i === 0 ? " active" : "");
            row.innerHTML = "<span>" + esc(f.name) + "</span><em>" + formatSize(f.size || 0) + "</em>";
            row.addEventListener("mousedown", function(e) {
                e.preventDefault();
                atState.index = i;
                applyAtSelection();
            });
            el.atPopup.appendChild(row);
        });
        if (items.length) {
            el.atPopup.hidden = false;
            placePopup(el.atPopup);
        } else {
            el.atPopup.hidden = true;
        }
    }
    function applyAtSelection() {
        if (!atState.match || !atState.items.length) return;
        var f = atState.items[atState.index];
        var v = el.prompt.value;
        el.prompt.value = v.slice(0, atState.match.start) + "@" + f.name + " " + v.slice(atState.match.end);
        el.atPopup.hidden = true;
        updateComposerState();
        el.prompt.focus();
    }
    var cmdState = {
        items: [],
        index: 0,
        match: null
    };
    function updateCmdPopup() {
        var pos = el.prompt.selectionStart || 0;
        var before = el.prompt.value.slice(0, pos);
        var m = before.match(/^\/([A-Za-z0-9_-]*)$/);
        if (!m) {
            el.cmdPopup.hidden = true;
            return;
        }
        var q = m[1].toLowerCase();
        var items = state.commands.filter(function(c) {
            return c.name.toLowerCase().indexOf(q) !== -1;
        }).slice(0, 10);
        cmdState.items = items;
        cmdState.match = {
            start: 1,
            end: pos
        };
        el.cmdPopup.innerHTML = "";
        items.forEach(function(c, i) {
            var row = document.createElement("div");
            row.className = "at-item cmd-item" + (i === 0 ? " active" : "");
            row.innerHTML = "<span>/" + esc(c.name) + "</span><em>" + esc(c.description || "") + "</em>";
            row.addEventListener("mousedown", function(e) {
                e.preventDefault();
                cmdState.index = i;
                applyCmdSelection();
            });
            el.cmdPopup.appendChild(row);
        });
        if (items.length) {
            el.cmdPopup.hidden = false;
            placePopup(el.cmdPopup);
        } else {
            el.cmdPopup.hidden = true;
        }
    }
    function applyCmdSelection() {
        if (!cmdState.match || !cmdState.items.length) return;
        var name = cmdState.items[cmdState.index].name;
        el.prompt.value = "/" + name + " ";
        el.cmdPopup.hidden = true;
        updateComposerState();
        el.prompt.focus();
    }
    function placePopup(popup) {
        var r = el.prompt.getBoundingClientRect();
        popup.style.left = r.left + "px";
        popup.style.width = r.width + "px";
        popup.style.bottom = window.innerHeight - r.top + 6 + "px";
    }
    function startRecording() {
        if (state.generating) return;
        state.recording = {
            start: Date.now(),
            timer: null
        };
        el.recordingBar.hidden = false;
        el.recordingTimer.textContent = "00:00";
        state.recording.timer = setInterval(function() {
            var sec = Math.floor((Date.now() - state.recording.start) / 1e3);
            el.recordingTimer.textContent = formatDuration(sec);
        }, 250);
        updateComposerState();
    }
    function cancelRecording() {
        if (!state.recording) return;
        clearInterval(state.recording.timer);
        state.recording = null;
        el.recordingBar.hidden = true;
        updateComposerState();
    }
    function sendRecording() {
        if (!state.recording) return;
        clearInterval(state.recording.timer);
        var sec = Math.floor((Date.now() - state.recording.start) / 1e3);
        state.recording = null;
        el.recordingBar.hidden = true;
        var empty = el.messages.querySelector(".empty-state");
        if (empty) empty.remove();
        var s = getCurrentSession();
        var proceed = s ? Promise.resolve() : createSession(t("voice"));
        proceed.then(function() {
            s = getCurrentSession();
            var m = {
                info: {
                    id: makeMsgId(),
                    role: "user",
                    time: {
                        created: Date.now(),
                        completed: Date.now()
                    }
                },
                parts: [ {
                    type: "voice",
                    duration: sec
                } ]
            };
            if (state.backend === "mock") {
                s.messages.push(m);
                s.updatedAt = Date.now();
                saveMockSessions();
                el.messages.appendChild(renderMessage(m));
                renderSessionList();
                scrollToBottom(true);
            } else {
                el.messages.appendChild(renderMessage(m));
                scrollToBottom(true);
                var body = {
                    parts: [ {
                        type: "text",
                        text: t("voiceNote") + formatDuration(sec) + ")"
                    } ]
                };
                state.generating = true;
                updateComposerState();
                setTyping(true);
                api("POST", "/session/" + state.sessionId + "/prompt_async", body).then(refresh).catch(function() {
                    state.generating = false;
                    updateComposerState();
                    setTyping(false);
                });
                return;
            }
            markUserMessagesRead();
            triggerAgentResponse("");
        });
        updateComposerState();
    }
    function openSidebar() {
        el.sidebar.classList.remove("hidden");
        if (window.innerWidth < 760) {
            el.sidebarBackdrop.classList.add("visible");
        }
    }
    function closeSidebar() {
        el.sidebar.classList.add("hidden");
        el.sidebarBackdrop.classList.remove("visible");
    }
    function toggleSidebar() {
        if (el.sidebar.classList.contains("hidden")) {
            openSidebar();
        } else {
            closeSidebar();
        }
    }
    var lastViewportMode = null;
    function syncViewportSidebar() {
        var mode = window.innerWidth < 760 ? "mobile" : "desktop";
        if (mode !== lastViewportMode) {
            if (mode === "mobile") {
                el.sidebar.classList.add("hidden");
                el.sidebarBackdrop.classList.remove("visible");
            } else {
                el.sidebar.classList.remove("hidden");
                el.sidebarBackdrop.classList.remove("visible");
            }
            lastViewportMode = mode;
        }
    }
    function newChat() {
        setPlan(true);
        state.sessionId = null;
        state.lastRendered = null;
        state.lastMsgs = [];
        state.forcedIdle = false;
        state.generating = false;
        state.stalePolls = 0;
        hideStuck();
        setTyping(false);
        updateComposerState();
        renderSessionList();
        renderMessages([]);
        clearQueuedMessages();
        resetRenderedMsgKeys();
        sessionDel(LAST_KEY + (state.user ? state.user.alias : ""));
        if (el.searchInput) el.searchInput.value = "";
        if (window.innerWidth < 760) closeSidebar();
        el.prompt.focus();
    }
    function renderUserBadge() {
        var alias = state.user ? state.user.alias : "";
        var isAdmin = !!(state.user && state.user.admin);
        el.meAvatar.textContent = alias || "–";
        el.meAlias.textContent = alias || "–";
        if (el.meAdminBadge) el.meAdminBadge.hidden = !isAdmin;
        if (el.adminUsersBtn) el.adminUsersBtn.hidden = !isAdmin;
    }
    function attachEvents() {
        el.loginCard.addEventListener("submit", handleLoginSubmit);
        el.loginToggle.addEventListener("click", function() {
            setLoginMode(loginMode === "register" ? "login" : "register");
        });
        el.loginAlias.addEventListener("input", function() {
            el.loginAlias.value = el.loginAlias.value.replace(/\s+/g, "");
            hideLoginError();
        });
        el.loginPin.addEventListener("input", hideLoginError);
        el.loginPin2.addEventListener("input", hideLoginError);
        if (el.planModeBtn) el.planModeBtn.addEventListener("click", togglePlanMode);
        el.messages.addEventListener("click", function(e) {
            var a = e.target && e.target.closest ? e.target.closest("a.file-mention") : null;
            if (!a) {
                return;
            }
            e.preventDefault();
            jumpToUpload(a.getAttribute("data-file"), a.closest(".msg"));
        });
        el.logout.addEventListener("click", logout);
        el.toggleSidebar.addEventListener("click", toggleSidebar);
        if (el.sidebarCollapse) el.sidebarCollapse.addEventListener("click", closeSidebar);
        if (el.toggleSidebarRight) el.toggleSidebarRight.addEventListener("click", openSidebar);
        if (el.chatAddBtn) el.chatAddBtn.addEventListener("click", openAddMember);
        if (el.chatRemoveBtn) el.chatRemoveBtn.addEventListener("click", openRemoveMember);
        if (el.chatMenuBtn) {
            el.chatMenuBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                toggleChatMenu();
            });
        }
        if (el.modelMenuBtn) {
            el.modelMenuBtn.addEventListener("click", function(e) {
                e.stopPropagation();
                toggleModelMenu();
            });
        }
        if (el.chatExportBtn) el.chatExportBtn.addEventListener("click", exportSession);
        if (el.chatDeleteBtn) el.chatDeleteBtn.addEventListener("click", deleteCurrentSession);
        if (el.appDialogOk) el.appDialogOk.addEventListener("click", function() {
            settleDialog(true);
        });
        if (el.appDialogCancel) el.appDialogCancel.addEventListener("click", function() {
            settleDialog(null);
        });
        if (el.appDialog) {
            el.appDialog.addEventListener("click", function(e) {
                if (e.target === el.appDialog) settleDialog(null);
            });
        }
        document.addEventListener("keydown", function(e) {
            if (dialogResolve && e.key === "Escape") settleDialog(null);
            if (dialogResolve && e.key === "Enter" && document.activeElement !== el.appDialogInput) settleDialog(true);
        });
        if (el.adminUsersBtn) el.adminUsersBtn.addEventListener("click", adminOpen);
        if (el.adminModalClose) el.adminModalClose.addEventListener("click", adminClose);
        if (el.adminModal) {
            el.adminModal.addEventListener("click", function(e) {
                if (e.target === el.adminModal) adminClose();
            });
        }
        if (el.adminAddForm) el.adminAddForm.addEventListener("submit", handleAdminAddSubmit);
        if (el.adminAllowReg) {
            el.adminAllowReg.addEventListener("change", function() {
                adminSetRegistration(el.adminAllowReg.checked);
            });
        }
        if (el.adminUserList) {
            el.adminUserList.addEventListener("click", function(e) {
                var btn = e.target && e.target.closest ? e.target.closest(".admin-act") : null;
                if (!btn) return;
                var act = btn.getAttribute("data-act");
                var alias = btn.getAttribute("data-alias");
                if (act === "pin") adminChangePin(alias); else if (act === "admin") adminToggleAdmin(alias); else if (act === "delete") adminDeleteUser(alias);
            });
        }
        el.composer.addEventListener("submit", function(e) {
            e.preventDefault();
            send(el.prompt.value);
        });
        el.prompt.addEventListener("input", function() {
            el.prompt.style.height = "auto";
            el.prompt.style.height = Math.min(el.prompt.scrollHeight, 120) + "px";
            updateComposerState();
            updateAtPopup();
            updateCmdPopup();
        });
        el.prompt.addEventListener("keydown", function(e) {
            if (!el.cmdPopup.hidden) {
                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    cmdState.index = (cmdState.index + 1) % cmdState.items.length;
                    updateCmdActive();
                } else if (e.key === "ArrowUp") {
                    e.preventDefault();
                    cmdState.index = (cmdState.index - 1 + cmdState.items.length) % cmdState.items.length;
                    updateCmdActive();
                } else if (e.key === "Enter" || e.key === "Tab") {
                    e.preventDefault();
                    applyCmdSelection();
                } else if (e.key === "Escape") {
                    el.cmdPopup.hidden = true;
                }
                return;
            }
            if (!el.atPopup.hidden) {
                if (e.key === "ArrowDown") {
                    e.preventDefault();
                    atState.index = (atState.index + 1) % atState.items.length;
                    updateAtActive();
                } else if (e.key === "ArrowUp") {
                    e.preventDefault();
                    atState.index = (atState.index - 1 + atState.items.length) % atState.items.length;
                    updateAtActive();
                } else if (e.key === "Enter" || e.key === "Tab") {
                    e.preventDefault();
                    applyAtSelection();
                } else if (e.key === "Escape") {
                    el.atPopup.hidden = true;
                }
                return;
            }
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                if (!state.generating) send(el.prompt.value);
            }
        });
        function hideAttachMenu() {
            if (el.attachMenu) {
                el.attachMenu.hidden = true;
            }
            document.removeEventListener("click", onDocClick, true);
        }
        function onDocClick(e) {
            if (el.attachBtn && el.attachBtn.contains(e.target)) {
                return;
            }
            hideAttachMenu();
        }
        function showAttachMenu() {
            if (!el.attachMenu) {
                return;
            }
            el.attachMenu.innerHTML = "";
            var opts = [ {
                icon: "📄",
                label: t("attachFileOption"),
                input: el.fileInput
            }, {
                icon: "📷",
                label: t("attachGalleryOption"),
                input: el.fileInputGallery
            } ];
            opts.forEach(function(o) {
                if (!o.input) {
                    return;
                }
                var b = document.createElement("button");
                b.type = "button";
                var ic = document.createElement("span");
                ic.className = "am-icon";
                ic.textContent = o.icon;
                var lb = document.createElement("span");
                lb.textContent = o.label;
                b.appendChild(ic);
                b.appendChild(lb);
                b.addEventListener("click", function(ev) {
                    ev.stopPropagation();
                    hideAttachMenu();
                    o.input.click();
                });
                el.attachMenu.appendChild(b);
            });
            var r = el.attachBtn.getBoundingClientRect();
            el.attachMenu.style.left = Math.max(8, r.left) + "px";
            el.attachMenu.style.bottom = window.innerHeight - r.top + 8 + "px";
            el.attachMenu.hidden = false;
            setTimeout(function() {
                document.addEventListener("click", onDocClick, true);
            }, 0);
        }
        el.attachBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            if (window.matchMedia && window.matchMedia("(pointer: coarse)").matches) {
                if (el.attachMenu && !el.attachMenu.hidden) {
                    hideAttachMenu();
                } else {
                    showAttachMenu();
                }
            } else {
                hideAttachMenu();
                el.fileInput.click();
            }
        });
        el.fileInput.addEventListener("change", function() {
            uploadFiles(el.fileInput.files);
            el.fileInput.value = "";
        });
        if (el.fileInputGallery) {
            el.fileInputGallery.addEventListener("change", function() {
                uploadFiles(el.fileInputGallery.files);
                el.fileInputGallery.value = "";
            });
        }
        el.micBtn.addEventListener("click", startRecording);
        el.cancelRecBtn.addEventListener("click", cancelRecording);
        el.sendRecBtn.addEventListener("click", sendRecording);
        el.stopBtn.addEventListener("click", stopGeneration);
        el.newChat.addEventListener("click", newChat);
        if (el.searchInput) {
            el.searchInput.addEventListener("input", renderSessionList);
        }
        el.sidebarBackdrop.addEventListener("click", closeSidebar);
        var dragCount = 0;
        window.addEventListener("dragenter", function(e) {
            e.preventDefault();
            dragCount++;
            el.dropOverlay.hidden = false;
        });
        window.addEventListener("dragleave", function() {
            dragCount--;
            if (dragCount <= 0) {
                dragCount = 0;
                el.dropOverlay.hidden = true;
            }
        });
        window.addEventListener("dragover", function(e) {
            e.preventDefault();
        });
        window.addEventListener("drop", function(e) {
            e.preventDefault();
            dragCount = 0;
            el.dropOverlay.hidden = true;
            if (e.dataTransfer && e.dataTransfer.files) {
                uploadFiles(e.dataTransfer.files);
            }
        });
        el.prompt.addEventListener("paste", function(e) {
            var cd = e.clipboardData;
            if (!cd) {
                return;
            }
            var pf = cd.files && cd.files.length ? Array.prototype.slice.call(cd.files) : [];
            if (!pf.length && cd.items) {
                for (var i = 0; i < cd.items.length; i++) {
                    var it = cd.items[i];
                    if (it.kind === "file" && it.type.indexOf("image/") === 0 && it.getAsFile) {
                        var blob = it.getAsFile();
                        if (blob) {
                            pf.push(blob);
                        }
                    }
                }
            }
            if (pf.length) {
                e.preventDefault();
                uploadFiles(pf);
            }
        });
        el.questionModalClose.addEventListener("click", function() {
            el.questionModal.hidden = true;
        });
        el.questionModal.addEventListener("click", function(e) {
            if (e.target === el.questionModal) el.questionModal.hidden = true;
        });
        window.addEventListener("resize", function() {
            if (!el.atPopup.hidden) placePopup(el.atPopup);
            if (!el.cmdPopup.hidden) placePopup(el.cmdPopup);
            syncViewportSidebar();
        });
        window.addEventListener("orientationchange", function() {
            setTimeout(syncViewportSidebar, 100);
        });
        document.addEventListener("click", function(e) {
            if (!el.atPopup.hidden && !el.atPopup.contains(e.target) && e.target !== el.prompt) {
                el.atPopup.hidden = true;
            }
            if (!el.cmdPopup.hidden && !el.cmdPopup.contains(e.target) && e.target !== el.prompt) {
                el.cmdPopup.hidden = true;
            }
            if (el.chatMenu && !el.chatMenu.hidden && el.chatActions && !el.chatActions.contains(e.target)) {
                closeChatMenu();
            }
            if (el.modelMenu && !el.modelMenu.hidden && el.modelMenuBtn && !el.modelMenuBtn.contains(e.target) && !el.modelMenu.contains(e.target)) {
                closeModelMenu();
            }
        });
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && !el.sidebar.classList.contains("hidden") && window.innerWidth < 760) {
                closeSidebar();
            }
        });
    }
    function updateAtActive() {
        var items = el.atPopup.querySelectorAll(".at-item");
        items.forEach(function(it, i) {
            it.classList.toggle("active", i === atState.index);
        });
    }
    function updateCmdActive() {
        var items = el.cmdPopup.querySelectorAll(".at-item");
        items.forEach(function(it, i) {
            it.classList.toggle("active", i === cmdState.index);
        });
    }
    function loadCommandsApi() {
        return api("GET", "/command").then(function(list) {
            var real = (list || []).filter(function(c) {
                return c && c.name;
            });
            real.forEach(function(c) {
                c._real = true;
            });
            var names = state.commands.map(function(c) {
                return c.name;
            });
            real.forEach(function(c) {
                if (names.indexOf(c.name) === -1) state.commands.push(c);
            });
            state.commands.sort(function(a, b) {
                return a.name.localeCompare(b.name);
            });
        }).catch(function() {});
    }
    function bootApp() {
        el.loginScreen.hidden = true;
        el.app.hidden = false;
        renderUserBadge();
        ensurePendingUploads();
        updateSessionTitle();
        setTyping(false);
        renderMessages([]);
        detectBackend().then(function() {
            if (state.backend === "api") {
                loadCommandsApi();
                loadIncomingApi();
                loadModelsForMenu();
            } else {
                loadIncomingMock();
            }
            loadSessions().then(function() {
                var last = sessionGet(LAST_KEY + state.user.alias);
                var pick = null;
                if (last && findSession(last)) {
                    pick = last;
                } else if (state.sessions.length) {
                    pick = state.sessions[0].id;
                }
                if (pick) {
                    switchSession(pick);
                } else {
                    state.sessionId = null;
                    renderSessionList();
                    renderMessages([]);
                    scrollToBottom(true);
                }
            });
        });
        syncViewportSidebar();
        updateComposerState();
        setTimeout(function() {
            el.prompt.focus();
        }, 100);
    }
    function init() {
        syncUsersFromServer().then(function() {
            attachEvents();
            renderPlanToggle();
            ensureAdminExists();
            var saved = sessionGet(USER_KEY);
            var users = loadUsers();
            if (saved && users[saved]) {
                state.user = {
                    alias: saved,
                    admin: !!(users[saved] && users[saved].admin)
                };
                bootApp();
                return;
            }
            var remembered = loadRemembered(users);
            if (remembered) {
                state.user = {
                    alias: remembered.alias,
                    admin: !!(users[remembered.alias] && users[remembered.alias].admin)
                };
                sessionSet(USER_KEY, remembered.alias);
                bootApp();
                return;
            }
            el.app.hidden = true;
            el.loginScreen.hidden = false;
            setLoginMode("login");
            setTimeout(function() {
                el.loginAlias.focus();
            }, 60);
        });
    }
    function rerenderI18n() {
        state.commands = state.commands.map(function(c) {
            if (c.name === "new") c.description = t("cmdNew");
            if (c.name === "help") c.description = t("cmdHelp");
            if (c.name === "clear") c.description = t("cmdClear");
            return c;
        });
        setLoginMode(loginMode);
        renderSessionList();
        renderModelMenu();
        renderUserBadge();
        if (state.user) {
            renderMessages(state.lastMsgs || []);
            el.statusText.textContent = state.generating ? t("statusTyping") : t("statusOnline");
        }
        renderInlineQuestions(false);
        if (!el.adminModal.hidden) renderAdminUsers();
    }
    document.addEventListener("i18n:change", rerenderI18n);
    setInterval(function() {
        if (state.user && state.backend === "api" && !document.hidden) {
            refresh();
        }
    }, 1500);
    document.addEventListener("DOMContentLoaded", init);
})();