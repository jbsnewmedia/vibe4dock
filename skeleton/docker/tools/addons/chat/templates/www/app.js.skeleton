(function() {
    "use strict";
    if (location.username || location.password) {
        try {
            history.replaceState(null, "", location.pathname + location.search + location.hash);
        } catch (e) {}
    }
    var state = {
        sessions: [],
        sessionId: null,
        commands: [],
        generating: false,
        lastRendered: null,
        pendingQuestions: [],
        pendingPermissions: [],
        lastPendingSig: null,
        stalePolls: 0,
        lastGenSig: null,
        stuckShown: false,
        forcedIdle: false,
        questionModalDismissed: false,
        replyError: ""
    };
    var el = {
        messages: document.getElementById("messages"),
        pending: document.getElementById("pending"),
        prompt: document.getElementById("prompt"),
        composer: document.getElementById("composer"),
        sendBtn: document.getElementById("send-btn"),
        stopBtn: document.getElementById("stop-btn"),
        sessionList: document.getElementById("session-list"),
        sessionTitle: document.getElementById("session-title"),
        newChat: document.getElementById("new-chat"),
        sidebar: document.getElementById("sidebar"),
        agentSelect: document.getElementById("agent-select"),
        modelSelect: document.getElementById("model-select"),
        stuck: document.getElementById("stuck"),
        questionModal: document.getElementById("question-modal"),
        questionModalBody: document.getElementById("question-modal-body"),
        questionModalCount: document.getElementById("question-modal-count"),
        questionModalClose: document.getElementById("question-modal-close"),
        working: document.getElementById("working"),
        attachBtn: document.getElementById("attach-btn"),
        fileInput: document.getElementById("file-input"),
        fileInputGallery: document.getElementById("file-input-gallery"),
        attachMenu: document.getElementById("attach-menu"),
        uploadStatus: document.getElementById("upload-status"),
        dropOverlay: document.getElementById("drop-overlay"),
        atPopup: document.getElementById("at-popup"),
        cmdPopup: document.getElementById("cmd-popup"),
        chatMain: document.getElementById("chat"),
        toggleSidebar: document.getElementById("toggle-sidebar")
    };
    var BASE = location.pathname.replace(/\/[^/]*$/, "/");
    var API_BASE = location.origin + BASE + "api";
    function sessionFromUrl() {
        var rest = location.pathname.slice(BASE.length);
        return /^ses_[A-Za-z0-9]+$/.test(rest) ? rest : null;
    }
    function setUrl(id) {
        var want = BASE + (id || "");
        if (location.pathname !== want) {
            try {
                history.pushState(null, "", want);
            } catch (e) {}
        }
    }
    function api(method, path, body) {
        return fetch(API_BASE + path, {
            method: method,
            headers: body ? {
                "Content-Type": "application/json"
            } : undefined,
            body: body ? JSON.stringify(body) : undefined
        }).then(function(r) {
            if (!r.ok) {
                throw new Error("HTTP " + r.status + " on " + path);
            }
            var ct = r.headers.get("content-type") || "";
            return ct.indexOf("json") !== -1 ? r.json() : r.text();
        });
    }
    function esc(s) {
        return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
    var AGENT_STORAGE_KEY = "vibe4dock.chat.agent";
    var MODEL_STORAGE_KEY = "vibe4dock.chat.model";
    var INTERNAL_AGENTS = [ "compaction", "summary", "title" ];
    var STUCK_MINUTES = (window.CHAT_CONFIG && window.CHAT_CONFIG.stuckMinutes) || 10;
    var STUCK_POLLS = Math.max(1, Math.round(STUCK_MINUTES * 60000 / 1500));
    var modelVariants = {};
    function modelVariantFor(pref) {
        if (!pref) {
            return null;
        }
        return modelVariants[pref.providerID + "/" + pref.modelID] || null;
    }
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
    function preferredAgent() {
        return storageGet(AGENT_STORAGE_KEY) || window.CHAT_CONFIG && window.CHAT_CONFIG.agent || "build";
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
            updateControls();
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
    function loadAgents() {
        if (!el.agentSelect) {
            return;
        }
        api("GET", "/agent").then(function(agents) {
            var usable = (agents || []).filter(function(a) {
                return a && a.mode === "primary" && !a.hidden && INTERNAL_AGENTS.indexOf(a.name) === -1;
            });
            if (!usable.length) {
                return;
            }
            usable.sort(function(a, b) {
                if (a.name === "build") {
                    return -1;
                }
                if (b.name === "build") {
                    return 1;
                }
                return a.name.localeCompare(b.name);
            });
            var preferred = preferredAgent();
            var hasPreferred = usable.some(function(a) {
                return a.name === preferred;
            });
            if (!hasPreferred) {
                preferred = usable[0].name;
            }
            el.agentSelect.innerHTML = "";
            usable.forEach(function(a) {
                var opt = document.createElement("option");
                opt.value = a.name;
                opt.textContent = a.name;
                if (a.description) {
                    opt.title = a.description;
                }
                el.agentSelect.appendChild(opt);
            });
            el.agentSelect.value = preferred;
        }).catch(function() {});
    }
    function loadCommands() {
        if (!el.cmdPopup) {
            return Promise.resolve();
        }
        return api("GET", "/command").then(function(list) {
            state.commands = (list || []).filter(function(c) {
                return c && c.name;
            }).sort(function(a, b) {
                return a.name.localeCompare(b.name);
            });
        }).catch(function() {});
    }
    function preferredModel() {
        var v = el.modelSelect ? el.modelSelect.value : "";
        if (v) {
            var i = v.indexOf("/");
            if (i > 0) {
                return {
                    providerID: v.slice(0, i),
                    modelID: v.slice(i + 1)
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
    function loadModels(attempt) {
        if (!el.modelSelect) {
            return Promise.resolve();
        }
        var tryN = attempt || 0;
        return api("GET", "/config/providers").then(function(res) {
            var providers = res && res.providers || [];
            var defaults = res && res.default || {};
            if (!providers.length) {
                throw new Error("no providers");
            }
            var known = {};
            providers.forEach(function(p) {
                var models = p.models || {};
                Object.keys(models).forEach(function(mid) {
                    var m = models[mid] || {};
                    known[p.id + "/" + (m.id || mid)] = {
                        label: m.name || m.id || mid,
                        prov: p.name || p.id,
                        def: defaults[p.id] === (m.id || mid),
                        zen: p.id === "opencode"
                    };
                });
            });
            var stateKeys = function(list) {
                return (Array.isArray(list) ? list : []).map(function(x) {
                    return x && x.providerID && x.modelID ? x.providerID + "/" + x.modelID : null;
                }).filter(function(k, i, a) {
                    return k && known[k] && a.indexOf(k) === i;
                });
            };
            var fillKeys = function(keys, label) {
                var group = document.createElement("optgroup");
                group.label = label;
                keys.forEach(function(key) {
                    var info = known[key];
                    var opt = document.createElement("option");
                    opt.value = key;
                    opt.textContent = info.label + " (" + info.prov + (info.def ? ", default" : "") + ")";
                    group.appendChild(opt);
                });
                if (group.children.length) {
                    el.modelSelect.appendChild(group);
                }
            };
            var finish = function(local) {
                el.modelSelect.innerHTML = "";
                var favorites = stateKeys(local && local.favorite);
                var recents = stateKeys(local && local.recent);
                if (favorites.length) {
                    fillKeys(favorites, t("modelsFav"));
                } else if (recents.length) {
                    fillKeys(recents, t("modelsRecent"));
                } else {
                    var zen = Object.keys(known).filter(function(k) {
                        return known[k].zen;
                    });
                    fillKeys(zen.length ? zen : Object.keys(known), zen.length ? t("modelsZen") : t("modelsAll"));
                }
                var candidates = [];
                var saved = storageGet(MODEL_STORAGE_KEY);
                if (saved) {
                    candidates.push(saved);
                }
                if (window.CHAT_CONFIG && window.CHAT_CONFIG.provider && window.CHAT_CONFIG.model) {
                    candidates.push(window.CHAT_CONFIG.provider + "/" + window.CHAT_CONFIG.model);
                }
                providers.forEach(function(p) {
                    if (defaults[p.id]) {
                        candidates.push(p.id + "/" + defaults[p.id]);
                    }
                });
                candidates.push(el.modelSelect.options[0] && el.modelSelect.options[0].value);
                for (var i = 0; i < candidates.length; i++) {
                    var v = candidates[i];
                    if (!v) {
                        continue;
                    }
                    var has = Array.prototype.some.call(el.modelSelect.options, function(o) {
                        return o.value === v;
                    });
                    if (has) {
                        el.modelSelect.value = v;
                        break;
                    }
                }
            };
            fetch(location.origin + BASE + "models.json", {
                cache: "no-store"
            }).then(function(r) {
                return r.ok ? r.json() : null;
            }).then(function(local) {
                modelVariants = local && local.variant || {};
                finish(local || null);
            }).catch(function() {
                finish(null);
            });
        }).catch(function() {
            if (tryN < 2) {
                return new Promise(function(r) {
                    setTimeout(r, 2500);
                }).then(function() {
                    return loadModels(tryN + 1);
                });
            }
            return Promise.resolve();
        });
    }
    function placePopup(popup) {
        if (!popup || popup.hidden) {
            return;
        }
        var r = el.prompt.getBoundingClientRect();
        popup.style.left = r.left + "px";
        popup.style.width = r.width + "px";
        popup.style.bottom = window.innerHeight - r.top + 6 + "px";
    }
    function toolSummary(part) {
        var st = part.state || {};
        var input = st.input || {};
        var lines = [];
        var name = part.tool || "tool";
        var status = st.status || "pending";
        var head = '<span class="tool-name">' + esc(name) + "</span> <em>(" + esc(status) + ")</em>";
        lines.push(head);
        if (input.command || input.cmd || input.description) {
            lines.push('<div class="tool-cmd">' + esc(input.command || input.cmd || input.description) + "</div>");
        } else if (input.filePath || input.path) {
            lines.push('<div class="tool-cmd">' + esc(input.filePath || input.path) + "</div>");
        }
        if (status === "completed" || status === "error") {
            var out = "";
            if (typeof st.output === "string") {
                out = st.output;
            } else if (st.metadata && typeof st.metadata.output === "string") {
                out = st.metadata.output;
            }
            if (out) {
                lines.push('<div class="tool-out">' + esc(out.slice(0, 4e3)) + "</div>");
            }
        }
        return '<div class="thinking-entry">' + lines.join("") + "</div>";
    }
    function renderMessage(m, isActive) {
        var info = m.info || m;
        var role = info.role || "user";
        var parts = m.parts || [];
        var wrap = document.createElement("div");
        wrap.className = "msg " + (role === "user" ? "user" : "assistant");
        var who = document.createElement("div");
        who.className = "who";
        who.textContent = role === "user" ? "You" : info.agent || "opencode";
        wrap.appendChild(who);
        var answer = document.createElement("div");
        answer.className = "bubble";
        var thinking = null;
        var thinkingBody = null;
        var thinkingCount = 0;
        if (info.error && role === "assistant" && info.error.name !== "MessageAbortedError") {
            var ebox = document.createElement("div");
            ebox.className = "assistant-error";
            ebox.textContent = assistantErrorText(info.error);
            answer.appendChild(ebox);
        }
        for (var i = 0; i < parts.length; i++) {
            var p = parts[i];
            if (p.type === "text" && p.text) {
                answer.insertAdjacentHTML("beforeend", md(p.text));
            } else if (p.type === "subtask" && p.prompt) {
                answer.insertAdjacentHTML("beforeend", md(p.prompt));
            } else if (p.type === "reasoning" && p.text) {
                ensureThinking();
                thinkingBody.insertAdjacentHTML("beforeend", '<div class="thinking-entry">' + md(p.text) + "</div>");
                thinkingCount++;
            } else if (p.type === "tool") {
                ensureThinking();
                thinkingBody.insertAdjacentHTML("beforeend", toolSummary(p));
                thinkingCount++;
            }
        }
        function ensureThinking() {
            if (thinking) {
                return;
            }
            thinking = document.createElement("details");
            thinking.className = "thinking";
            var summary = document.createElement("summary");
            summary.innerHTML = '<span class="spinner"></span>' + t("thinkingTools");
            thinking.appendChild(summary);
            thinkingBody = document.createElement("div");
            thinkingBody.className = "thinking-body";
            thinking.appendChild(thinkingBody);
        }
        if (thinking) {
            var active = isActive && state.generating;
            var summary = thinking.querySelector("summary");
            summary.innerHTML = (active ? '<span class="spinner"></span>' : "") + "Thinking &amp; Tools (" + thinkingCount + ")";
            if (active && thinkingCount > 0) {
                thinking.open = true;
            }
            wrap.appendChild(thinking);
        }
        if (answer.childNodes.length) {
            wrap.appendChild(answer);
        }
        return wrap;
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
    function isComplete(m) {
        var info = m.info || m;
        var t = info.time || {};
        return !!t.completed;
    }
    function render(messages) {
        var list = (messages || []).filter(function(m) {
            var info = m.info || m;
            return info.role === "user" || info.role === "assistant";
        });
        el.messages.innerHTML = "";
        if (!list.length) {
            el.messages.innerHTML = '<div class="empty-state" data-i18n="emptyState">' + esc(t("emptyState")) + "</div>";
            return;
        }
        list.forEach(function(m, idx) {
            el.messages.appendChild(renderMessage(m, idx === list.length - 1));
        });
        el.messages.scrollTop = el.messages.scrollHeight;
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
    function renderPending() {
        var sig = JSON.stringify([ state.pendingQuestions, state.pendingPermissions ]);
        var isNew = sig !== state.lastPendingSig;
        if (!isNew) {
            return;
        }
        state.lastPendingSig = sig;
        if (isNew) {
            state.questionModalDismissed = false;
        }
        var body = el.questionModalBody;
        body.innerHTML = "";
        var count = pendingCount();
        el.questionModalCount.textContent = count;
        el.questionModal.hidden = count === 0 || state.questionModalDismissed;
        if (state.replyError) {
            var errBox = document.createElement("div");
            errBox.className = "pending-box";
            errBox.textContent = t("errPrefix") + state.replyError;
            body.appendChild(errBox);
        }
        state.pendingQuestions.forEach(function(req) {
            var box = document.createElement("div");
            box.className = "pending-box";
            var answers = [];
            var errors = [];
            (req.questions || []).forEach(function(q, qIdx) {
                var head = document.createElement("div");
                head.className = "q";
                head.textContent = (q.header ? q.header + " – " : "") + (q.question || t("question"));
                box.appendChild(head);
                var picked = null;
                var pickedBtn = null;
                var opts = q.options || [];
                if (opts.length) {
                    var optRow = document.createElement("div");
                    optRow.className = "opts";
                    opts.forEach(function(o) {
                        var label = typeof o === "string" ? o : o.label || "";
                        var b = document.createElement("button");
                        b.type = "button";
                        b.className = "btn" + (o && o.description ? " has-desc" : "");
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
                            if (pickedBtn === b) {
                                picked = null;
                                pickedBtn = null;
                                b.classList.remove("btn-primary");
                            } else {
                                if (pickedBtn) {
                                    pickedBtn.classList.remove("btn-primary");
                                }
                                picked = label;
                                pickedBtn = b;
                                b.classList.add("btn-primary");
                                errors[qIdx].hidden = true;
                            }
                            if (input.value) {
                                input.value = "";
                                input.classList.remove("invalid");
                            }
                        });
                        optRow.appendChild(b);
                    });
                    box.appendChild(optRow);
                }
                var input = document.createElement("input");
                input.type = "text";
                input.placeholder = t("ownAnswerPh");
                input.addEventListener("input", function() {
                    if (input.value.trim()) {
                        if (pickedBtn) {
                            pickedBtn.classList.remove("btn-primary");
                            picked = null;
                            pickedBtn = null;
                        }
                        errors[qIdx].hidden = true;
                        input.classList.remove("invalid");
                    }
                });
                box.appendChild(input);
                var err = document.createElement("div");
                err.className = "q-error";
                err.textContent = t("answerRequired");
                err.hidden = true;
                box.appendChild(err);
                errors.push(err);
                answers.push(function() {
                    if (picked) {
                        return [ picked ];
                    }
                    var free = input.value.trim();
                    return free ? [ free ] : [];
                });
            });
            var send = document.createElement("button");
            send.type = "button";
            send.className = "btn btn-primary";
            send.textContent = t("sendAnswer");
            send.addEventListener("click", function() {
                var vals = answers.map(function(fn) {
                    return fn();
                });
                var firstBad = -1;
                answers.forEach(function(fn, idx) {
                    var empty = !fn().length;
                    errors[idx].hidden = !empty;
                    if (empty) {
                        var inp = box.querySelectorAll("input[type=text]")[idx];
                        if (inp) {
                            inp.classList.add("invalid");
                        }
                        if (firstBad < 0) {
                            firstBad = idx;
                        }
                    } else {
                        var inpOk = box.querySelectorAll("input[type=text]")[idx];
                        if (inpOk) {
                            inpOk.classList.remove("invalid");
                        }
                    }
                });
                if (firstBad >= 0) {
                    var badInp = box.querySelectorAll("input[type=text]")[firstBad];
                    if (badInp) {
                        badInp.focus();
                    }
                    return;
                }
                state.replyError = "";
                answerQuestion(req, vals);
            });
            box.appendChild(send);
            body.appendChild(box);
        });
        state.pendingPermissions.forEach(function(p) {
            var box = document.createElement("div");
            box.className = "pending-box";
            var title = document.createElement("div");
            title.className = "q";
            title.textContent = t("permPrefix") + (p.title || p.type || t("permAction"));
            box.appendChild(title);
            var opts = document.createElement("div");
            opts.className = "opts";
            [ [ "once", t("permAllow") ], [ "always", t("permAlways") ], [ "reject", t("permDeny") ] ].forEach(function(pair) {
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
            if (count > 0 && state.questionModalDismissed) {
                var showBtn = document.createElement("button");
                showBtn.type = "button";
                showBtn.className = "btn btn-primary";
                showBtn.textContent = t("showQuestions", {
                    n: count
                });
                showBtn.addEventListener("click", function() {
                    state.questionModalDismissed = false;
                    el.questionModal.hidden = false;
                    el.pending.innerHTML = "";
                });
                el.pending.appendChild(showBtn);
            }
        }
    }
    function hideQuestionModal() {
        state.questionModalDismissed = true;
        el.questionModal.hidden = true;
        if (el.pending) {
            var count = pendingCount();
            el.pending.innerHTML = "";
            if (count > 0) {
                var showBtn = document.createElement("button");
                showBtn.type = "button";
                showBtn.className = "btn btn-primary";
                showBtn.textContent = t("showQuestions", {
                    n: count
                });
                showBtn.addEventListener("click", function() {
                    state.questionModalDismissed = false;
                    el.questionModal.hidden = false;
                    el.pending.innerHTML = "";
                });
                el.pending.appendChild(showBtn);
            }
        }
    }
    function answerQuestion(req, payload) {
        var reply = api("POST", "/question/" + encodeURIComponent(req.id || req.requestID) + "/reply", {
            answers: payload
        });
        var done = function() {
            state.replyError = "";
            return refresh();
        };
        var fail = function(e) {
            state.replyError = "Could not send answer: " + e.message;
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
            state.replyError = "Could not send permission reply: " + e.message;
            renderPending();
        });
    }
    function loadSessions() {
        return api("GET", "/session").then(function(sessions) {
            state.sessions = (sessions || []).filter(function(s) {
                return !s.parentID;
            });
            el.sessionList.innerHTML = "";
            state.sessions.forEach(function(s) {
                var li = document.createElement("li");
                if (s.id === state.sessionId) {
                    li.className = "active";
                    el.sessionTitle.textContent = s.title || s.id;
                }
                var label = document.createElement("span");
                label.className = "session-label";
                label.textContent = s.title || s.id;
                li.appendChild(label);
                var del = document.createElement("button");
                del.className = "session-delete";
                del.textContent = "×";
                del.title = t("deleteChatTitle");
                del.addEventListener("click", function(e) {
                    e.stopPropagation();
                    if (del.dataset.confirm !== "1") {
                        del.dataset.confirm = "1";
                        del.classList.add("confirm");
                        del.textContent = "✓";
                        setTimeout(function() {
                            del.dataset.confirm = "";
                            del.classList.remove("confirm");
                            del.textContent = "×";
                        }, 2500);
                        return;
                    }
                    api("DELETE", "/session/" + encodeURIComponent(s.id), {}).then(function() {
                        if (state.sessionId === s.id) {
                            state.sessionId = null;
                            state.lastRendered = null;
                            state.lastPendingSig = null;
                            el.sessionTitle.textContent = t("sessionChat");
                            el.messages.innerHTML = '<div class="empty-state" data-i18n="emptyState">' + esc(t("emptyState")) + "</div>";
                        }
                        return loadSessions();
                    }).catch(function() {});
                });
                li.appendChild(del);
                li.addEventListener("click", function() {
                    selectSession(s.id);
                });
                el.sessionList.appendChild(li);
            });
        });
    }
    function refresh() {
        var jobs = [ loadSessions() ];
        if (state.sessionId) {
            jobs.push(api("GET", "/session/" + state.sessionId + "/message").then(function(msgs) {
                var wasGenerating = state.generating;
                state.generating = state.forcedIdle ? false : (msgs || []).some(function(m) {
                    var info = m.info || m;
                    return info.role === "assistant" && !(info.time && info.time.completed);
                });
                trackGeneration(msgs);
                if (wasGenerating !== state.generating || !state.lastRendered || JSON.stringify(msgs).length !== state.lastRendered) {
                    render(msgs);
                    state.lastRendered = JSON.stringify(msgs).length;
                }
                state.lastMsgs = msgs;
                updateControls();
            }));
            jobs.push(api("GET", "/question").then(function(qs) {
                var live = (qs || []).filter(function(q) {
                    return !q.sessionID || q.sessionID === state.sessionId;
                });
                state.pendingQuestions = live.length ? live : liveQuestions(state.lastMsgs) || [];
                renderPending();
            }).catch(function() {
                state.pendingQuestions = liveQuestions(state.lastMsgs) || [];
            }));
            jobs.push(api("GET", "/permission").then(function(ps) {
                var list = Array.isArray(ps) ? ps : ps && ps.permissions || [];
                state.pendingPermissions = list.filter(function(p) {
                    return !p.sessionID || p.sessionID === state.sessionId;
                });
                renderPending();
            }).catch(function() {
                state.pendingPermissions = [];
            }));
        }
        return Promise.all(jobs.map(function(p) {
            return p.catch(function() {});
        }));
    }
    function updateControls() {
        el.stopBtn.hidden = !state.generating;
        el.sendBtn.disabled = state.generating;
        if (el.working) {
            el.working.hidden = !state.generating;
        }
    }
    function selectSession(id) {
        state.sessionId = id;
        setUrl(id);
        state.lastRendered = null;
        state.lastPendingSig = null;
        var s = state.sessions.find(function(x) {
            return x.id === id;
        });
        el.sessionTitle.textContent = s && (s.title || s.id) || t("sessionChat");
        refresh().then(function() {
            el.sessionList.querySelectorAll("li").forEach(function(li) {
                li.classList.remove("active");
            });
        });
    }
    function resetToFresh() {
        state.sessionId = null;
        state.lastRendered = null;
        state.lastMsgs = [];
        state.pendingQuestions = [];
        state.pendingPermissions = [];
        state.replyError = "";
        state.lastPendingSig = null;
        state.generating = false;
        state.forcedIdle = false;
        state.stalePolls = 0;
        state.lastGenSig = null;
        hideStuck();
        renderPending();
        el.sessionTitle.textContent = t("sessionChat");
        render([]);
        updateControls();
        return loadSessions();
    }
    function newChat() {
        setUrl("");
        resetToFresh().then(function() {
            el.prompt.focus();
        });
    }
    var incomingCache = [];
    function formatSize(bytes) {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(1) + " MB";
        }
        if (bytes >= 1024) {
            return Math.round(bytes / 1024) + " KB";
        }
        return bytes + " B";
    }
    var atState = {
        items: [],
        index: 0,
        match: null
    };
    function loadIncoming() {
        return api("GET", "/upload/list").then(function(res) {
            incomingCache = res && res.files || [];
        }).catch(function() {});
    }
    function insertAtCursor(text) {
        var pos = el.prompt.selectionStart || 0;
        var v = el.prompt.value;
        el.prompt.value = v.slice(0, pos) + text + v.slice(pos);
        var np = pos + text.length;
        el.prompt.setSelectionRange(np, np);
        el.prompt.focus();
    }
    function uploadFile(file) {
        return new Promise(function(resolve) {
            var fd = new FormData;
            fd.append("file", file);
            var xhr = new XMLHttpRequest;
            xhr.open("POST", API_BASE + "/upload");
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable && el.uploadStatus) {
                    el.uploadStatus.textContent = t("uploadPct", {
                        name: file.name,
                        pct: Math.round(e.loaded / e.total * 100)
                    });
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
        var list = Array.prototype.slice.call(fileList || []);
        if (!list.length) {
            return;
        }
        (function next(i) {
            if (i >= list.length) {
                el.uploadStatus.hidden = true;
                loadIncoming();
                return;
            }
            var f = list[i];
            el.uploadStatus.hidden = false;
            el.uploadStatus.textContent = t("uploadIdx", {
                name: f.name,
                i: i + 1,
                n: list.length
            });
            uploadFile(f).then(function(res) {
                if (res && res.ok && res.files && res.files[0] && res.files[0].ok) {
                    el.uploadStatus.textContent = t("attached", {
                        name: res.files[0].name
                    });
                    insertAtCursor("@" + res.files[0].name + " ");
                    el.prompt.focus();
                } else {
                    el.uploadStatus.textContent = t("uploadFailed", {
                        name: f.name
                    });
                }
                setTimeout(function() {
                    next(i + 1);
                }, 250);
            });
        })(0);
    }
    function hideAtPopup() {
        el.atPopup.hidden = true;
        atState.items = [];
    }
    function updateAtPopup() {
        if (!state.incomingLoaded || !incomingCache.length) {
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
        var matches = incomingCache.filter(function(f) {
            return f.name.toLowerCase().indexOf(q) !== -1;
        });
        var items = matches.slice(0, 10);
        if (!items.length) {
            el.atPopup.hidden = true;
            return;
        }
        atState.items = items;
        atState.index = 0;
        el.atPopup.innerHTML = "";
        var head = document.createElement("div");
        head.className = "at-head";
        head.textContent = !q && incomingCache.length > 10 ? "10 of " + incomingCache.length + " files - type to search" : q ? matches.length + " matches - live search" : "";
        if (head.textContent) {
            el.atPopup.appendChild(head);
        }
        items.forEach(function(f, i) {
            var row = document.createElement("div");
            row.className = "at-item" + (i === 0 ? " active" : "");
            var label = document.createElement("span");
            label.textContent = f.name;
            row.appendChild(label);
            var meta = document.createElement("em");
            meta.textContent = formatSize(f.size);
            row.appendChild(meta);
            row.addEventListener("click", function() {
                applyAtSelection();
            });
            el.atPopup.appendChild(row);
        });
        el.atPopup.hidden = false;
        placePopup(el.atPopup);
    }
    function applyAtSelection() {
        var f = atState.items[atState.index];
        if (!f) {
            el.atPopup.hidden = true;
            return;
        }
        var v = el.prompt.value;
        var ins = "@" + f.name + " ";
        el.prompt.value = v.slice(0, atState.match.start) + ins + v.slice(atState.match.end);
        var np = atState.match.start + ins.length;
        el.prompt.setSelectionRange(np, np);
        el.atPopup.hidden = true;
        el.prompt.focus();
    }
    var cmdState = {
        items: [],
        index: 0,
        match: null
    };
    function hideCmdPopup() {
        if (!el.cmdPopup) {
            return;
        }
        el.cmdPopup.hidden = true;
        cmdState.items = [];
    }
    function updateCmdPopup() {
        if (!el.cmdPopup || !state.commands.length) {
            return;
        }
        var pos = el.prompt.selectionStart || 0;
        var before = el.prompt.value.slice(0, pos);
        var m = before.match(/^\/([A-Za-z0-9_-]*)$/);
        if (!m) {
            el.cmdPopup.hidden = true;
            return;
        }
        var q = m[1].toLowerCase();
        cmdState.match = {
            start: 0,
            end: pos
        };
        var matches = state.commands.filter(function(c) {
            return c.name.toLowerCase().indexOf(q) !== -1;
        });
        var items = matches.slice(0, 5);
        if (!items.length) {
            el.cmdPopup.hidden = true;
            return;
        }
        cmdState.items = items;
        cmdState.index = 0;
        el.cmdPopup.innerHTML = "";
        var head = document.createElement("div");
        head.className = "at-head";
        head.textContent = matches.length > items.length ? t("cmdFilter", {
            m: items.length,
            n: matches.length
        }) : q ? matches.length + " match" + (matches.length === 1 ? "" : "es") : "";
        if (head.textContent) {
            el.cmdPopup.appendChild(head);
        }
        items.forEach(function(c, i) {
            var row = document.createElement("div");
            row.className = "at-item cmd-item" + (i === 0 ? " active" : "");
            var label = document.createElement("span");
            label.textContent = "/" + c.name;
            row.appendChild(label);
            var meta = document.createElement("em");
            meta.textContent = c.description || "";
            row.appendChild(meta);
            if (c.description) {
                row.title = c.description;
            }
            row.addEventListener("click", function() {
                applyCmdSelection();
            });
            el.cmdPopup.appendChild(row);
        });
        el.cmdPopup.hidden = false;
        placePopup(el.cmdPopup);
    }
    function highlightCmdItem() {
        var rows = el.cmdPopup.querySelectorAll(".at-item");
        rows.forEach(function(r, i) {
            r.classList.toggle("active", i === cmdState.index);
        });
    }
    function applyCmdSelection() {
        var c = cmdState.items[cmdState.index];
        if (!el.cmdPopup || !c) {
            hideCmdPopup();
            return;
        }
        var ins = "/" + c.name + " ";
        var v = el.prompt.value;
        el.prompt.value = ins + v.slice(cmdState.match.end);
        var np = ins.length;
        el.prompt.setSelectionRange(np, np);
        el.cmdPopup.hidden = true;
        el.prompt.focus();
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
    function runCommand(found) {
        var cmd = found.command;
        var body = {
            command: cmd.name,
            arguments: expandMentions(found.args)
        };
        var agent = cmd.agent || el.agentSelect && el.agentSelect.value || "";
        if (agent) {
            body.agent = agent;
        }
        var cmdModel = preferredModel();
        if (cmdModel) {
            body.model = cmdModel.providerID + "/" + cmdModel.modelID;
        }
        var ensure = state.sessionId ? Promise.resolve() : api("POST", "/session", {}).then(function(s) {
            state.sessionId = s.id;
            setUrl(s.id);
        });
        ensure.then(function() {
            el.prompt.value = "";
            el.prompt.style.height = "auto";
            state.forcedIdle = false;
            state.stalePolls = 0;
            state.lastGenSig = null;
            state.generating = true;
            updateControls();
            return api("POST", "/session/" + state.sessionId + "/command", body).then(function() {
                return refresh();
            }).catch(function(e) {
                state.generating = false;
                updateControls();
                el.pending.innerHTML = '<div class="pending-box"><div class="q">' + esc(t("cmdErr")) + esc(e.message) + "</div></div>";
            });
        }).catch(function(e) {
            state.generating = false;
            updateControls();
            el.pending.innerHTML = '<div class="pending-box"><div class="q">' + esc(t("errPrefix")) + esc(e.message) + "</div></div>";
        });
    }
    function expandMentions(text) {
        if (!incomingCache.length) {
            return text;
        }
        return String(text).replace(/(^|\s)@([^\s@]+)/g, function(whole, pre, name) {
            if (name.toLowerCase().indexOf("incoming/") === 0) {
                return whole;
            }
            var found = incomingCache.some(function(f) {
                return f.name.toLowerCase() === name.toLowerCase();
            });
            return found ? pre + "@incoming/" + name : whole;
        });
    }
    function send(text) {
        if (!text || state.generating) {
            return;
        }
        var found = findCommand(text);
        if (found) {
            runCommand(found);
            return;
        }
        text = expandMentions(text);
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
            var variant = modelVariantFor(pref);
            if (variant) {
                body.variant = variant;
            }
        }
        var agent = el.agentSelect && el.agentSelect.value;
        if (agent) {
            body.agent = agent;
        }
        var ensure = state.sessionId ? Promise.resolve() : api("POST", "/session", {}).then(function(s) {
            state.sessionId = s.id;
            setUrl(s.id);
        });
        ensure.then(function() {
            el.prompt.value = "";
            el.prompt.style.height = "auto";
            state.forcedIdle = false;
            state.stalePolls = 0;
            state.lastGenSig = null;
            state.generating = true;
            updateControls();
            return api("POST", "/session/" + state.sessionId + "/prompt_async", body);
        }).then(function() {
            return refresh();
        }).catch(function(e) {
            state.generating = false;
            updateControls();
            el.pending.innerHTML = '<div class="pending-box"><div class="q">' + esc(t("errPrefix")) + esc(e.message) + "</div>" + t("providerHint") + "</div>";
        });
    }
    el.composer.addEventListener("submit", function(e) {
        e.preventDefault();
        send(el.prompt.value.trim());
    });
    el.prompt.addEventListener("keydown", function(e) {
        if (el.cmdPopup && !el.cmdPopup.hidden) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                cmdState.index = Math.min(cmdState.index + 1, cmdState.items.length - 1);
                highlightCmdItem();
                return;
            }
            if (e.key === "ArrowUp") {
                e.preventDefault();
                cmdState.index = Math.max(cmdState.index - 1, 0);
                highlightCmdItem();
                return;
            }
            if (e.key === "Enter" || e.key === "Tab") {
                e.preventDefault();
                applyCmdSelection();
                return;
            }
            if (e.key === "Escape") {
                e.preventDefault();
                hideCmdPopup();
                return;
            }
        }
        if (!el.atPopup.hidden) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                atState.index = Math.min(atState.index + 1, atState.items.length - 1);
                highlightAtItem();
                return;
            }
            if (e.key === "ArrowUp") {
                e.preventDefault();
                atState.index = Math.max(atState.index - 1, 0);
                highlightAtItem();
                return;
            }
            if (e.key === "Enter" || e.key === "Tab") {
                e.preventDefault();
                applyAtSelection();
                return;
            }
            if (e.key === "Escape") {
                e.preventDefault();
                hideAtPopup();
                return;
            }
        }
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault();
            send(el.prompt.value.trim());
        }
    });
    function highlightAtItem() {
        var rows = el.atPopup.querySelectorAll(".at-item");
        rows.forEach(function(r, i) {
            r.classList.toggle("active", i === atState.index);
        });
    }
    el.prompt.addEventListener("input", function() {
        el.prompt.style.height = "auto";
        el.prompt.style.height = Math.min(el.prompt.scrollHeight, 180) + "px";
        updateAtPopup();
        updateCmdPopup();
    });
    el.stopBtn.addEventListener("click", function() {
        if (state.sessionId) {
            api("POST", "/session/" + state.sessionId + "/abort", {}).then(refresh).catch(function() {});
        }
    });
    el.newChat.addEventListener("click", newChat);
    el.toggleSidebar.addEventListener("click", function() {
        el.sidebar.classList.toggle("hidden");
    });
    if (el.questionModalClose) {
        el.questionModalClose.addEventListener("click", hideQuestionModal);
    }
    if (el.questionModal) {
        el.questionModal.addEventListener("click", function(e) {
            if (e.target === el.questionModal) {
                hideQuestionModal();
            }
        });
    }
    if (el.agentSelect) {
        el.agentSelect.addEventListener("change", function() {
            storageSet(AGENT_STORAGE_KEY, el.agentSelect.value);
        });
    }
    if (el.modelSelect) {
        el.modelSelect.addEventListener("change", function() {
            storageSet(MODEL_STORAGE_KEY, el.modelSelect.value);
        });
    }
    window.addEventListener("resize", function() {
        placePopup(el.atPopup);
        placePopup(el.cmdPopup);
    });
    loadAgents();
    loadModels();
    loadCommands();
    loadIncoming().then(function() {
        state.incomingLoaded = true;
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
    if (el.attachBtn && el.fileInput) {
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
    }
    if (el.chatMain) {
        var dragDepth = 0;
        [ "dragenter", "dragover" ].forEach(function(ev) {
            el.chatMain.addEventListener(ev, function(e) {
                if (e.dataTransfer && Array.prototype.indexOf.call(e.dataTransfer.types, "Files") !== -1) {
                    e.preventDefault();
                    el.dropOverlay.hidden = false;
                }
            });
        });
        [ "dragleave", "drop" ].forEach(function(ev) {
            el.chatMain.addEventListener(ev, function(e) {
                if (ev === "drop") {
                    e.preventDefault();
                    uploadFiles(e.dataTransfer.files);
                }
                el.dropOverlay.hidden = true;
            });
        });
    }
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
                        pf.push(blob.name && blob.name !== "image.png" ? blob : new File([ blob ], "image-" + Date.now() + ".png", {
                            type: "image/png"
                        }));
                    }
                }
            }
        }
        if (pf.length) {
            e.preventDefault();
            uploadFiles(pf);
        }
    });
    refresh().then(function() {
        var wanted = sessionFromUrl();
        if (wanted) {
            var byUrl = state.sessions.find(function(x) {
                return x.id === wanted;
            });
            if (byUrl) {
                selectSession(byUrl.id);
                return;
            }
            location.replace(BASE);
            return;
        }
        render([]);
    });
    window.addEventListener("popstate", function() {
        var wanted = sessionFromUrl();
        if (wanted) {
            if (wanted !== state.sessionId) {
                var found = state.sessions.find(function(x) {
                    return x.id === wanted;
                });
                if (found) {
                    selectSession(found.id);
                } else {
                    location.replace(BASE);
                }
            }
        } else if (state.sessionId) {
            resetToFresh();
        }
    });
    document.addEventListener("i18n:change", function() {
        state.lastPendingSig = null;
        state.lastRendered = null;
        renderPending();
        loadSessions();
        refresh();
    });
    setInterval(refresh, 1500);
})();