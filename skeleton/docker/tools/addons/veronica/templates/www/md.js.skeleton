(function() {
    "use strict";
    function esc(s) {
        return String(s == null ? "" : s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }
    function mdInline(s) {
        return s.replace(/`([^`\n]+)`/g, "<code>$1</code>").replace(/\[([^\]\n]+)\]\((https?:[^)\s]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>').replace(/&lt;(https?:[^<\s]+)&gt;/g, '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>').replace(/\*\*([^*\n]+)\*\*/g, "<strong>$1</strong>").replace(/(^|[^*\w])\*([^*\n]+)\*(?!\w)/g, "$1<em>$2</em>").replace(/~~([^~\n]+)~~/g, "<del>$1</del>");
    }
    function splitRow(l) {
        l = l.trim();
        if (l.charAt(0) === "|") {
            l = l.slice(1);
        }
        if (l.charAt(l.length - 1) === "|") {
            l = l.slice(0, -1);
        }
        return l.split("|").map(function(c) {
            return c.trim();
        });
    }
    function colAlign(cell) {
        var t = cell.trim();
        if (/^:-+:$/.test(t)) {
            return "center";
        }
        if (/^-+:$/.test(t)) {
            return "right";
        }
        if (/^:-+$/.test(t)) {
            return "left";
        }
        return null;
    }
    function parseList(lines, start, out) {
        var i = start;
        var stack = [];
        var liOpen = false;
        function closeLi() {
            if (liOpen) {
                out.push("</li>");
                liOpen = false;
            }
        }
        function openList(tag, indent) {
            out.push("<" + tag + ">");
            stack.push({
                tag: tag,
                indent: indent
            });
            liOpen = false;
        }
        function closeList() {
            closeLi();
            out.push("</" + stack.pop().tag + ">");
        }
        while (i < lines.length) {
            var m = lines[i].match(/^(\s*)([-*+]|\d+[.)])\s+(.*)$/);
            if (!m) {
                break;
            }
            var indent = m[1].replace(/\t/g, "  ").length;
            var tag = /^[-*+]$/.test(m[2]) ? "ul" : "ol";
            var content = m[3];
            while (stack.length && stack[stack.length - 1].indent > indent) {
                closeList();
            }
            if (!stack.length || stack[stack.length - 1].indent < indent) {
                openList(tag, indent);
            } else if (stack[stack.length - 1].tag !== tag) {
                closeList();
                openList(tag, indent);
            } else {
                closeLi();
            }
            var tm = content.match(/^\[([ xX])\]\s*(.*)$/);
            if (tm) {
                out.push('<li class="task"><input type="checkbox" disabled' + (tm[1] !== " " ? " checked" : "") + ">");
                content = tm[2];
            } else {
                out.push("<li>");
            }
            liOpen = true;
            out.push(mdInline(content));
            i++;
        }
        while (stack.length) {
            closeList();
        }
        return i;
    }
    function mdBlock(seg, out) {
        var lines = seg.split("\n");
        var i = 0;
        var para = [];
        function flushPara() {
            if (para.length) {
                out.push("<p>" + para.map(mdInline).join("<br>") + "</p>");
                para = [];
            }
        }
        while (i < lines.length) {
            var line = lines[i];
            if (!line.trim()) {
                flushPara();
                i++;
                continue;
            }
            var h = line.match(/^(#{1,4})\s+(.*)$/);
            if (h) {
                flushPara();
                out.push("<h" + h[1].length + ">" + mdInline(h[2]) + "</h" + h[1].length + ">");
                i++;
                continue;
            }
            if (/^\s*(-{3,}|\*{3,}|_{3,})\s*$/.test(line)) {
                flushPara();
                out.push("<hr>");
                i++;
                continue;
            }
            if (/^\s*&gt;/.test(line)) {
                flushPara();
                var quote = [];
                while (i < lines.length && /^\s*&gt;/.test(lines[i])) {
                    quote.push(lines[i].replace(/^\s*&gt;\s?/, ""));
                    i++;
                }
                out.push("<blockquote>" + quote.map(mdInline).join("<br>") + "</blockquote>");
                continue;
            }
            if (/^(\s*)([-*+]|\d+[.)])\s+/.test(line)) {
                flushPara();
                i = parseList(lines, i, out);
                continue;
            }
            if (line.indexOf("|") !== -1 && i + 1 < lines.length && lines[i + 1].indexOf("|") !== -1 && /^\s*\|?[\s:|-]*-[\s:|-]*$/.test(lines[i + 1])) {
                flushPara();
                var header = splitRow(line);
                var aligns = splitRow(lines[i + 1]).map(colAlign);
                i += 2;
                var body = [];
                while (i < lines.length && lines[i].indexOf("|") !== -1 && lines[i].trim()) {
                    body.push(splitRow(lines[i]));
                    i++;
                }
                var cell = function(c, j, tag) {
                    var a = aligns[j] ? ' style="text-align:' + aligns[j] + '"' : "";
                    return "<" + tag + a + ">" + mdInline(c) + "</" + tag + ">";
                };
                out.push("<table><thead><tr>" + header.map(function(c, j) {
                    return cell(c, j, "th");
                }).join("") + "</tr></thead>");
                out.push("<tbody>" + body.map(function(r) {
                    return "<tr>" + r.map(function(c, j) {
                        return cell(c, j, "td");
                    }).join("") + "</tr>";
                }).join("") + "</tbody></table>");
                continue;
            }
            para.push(line);
            i++;
        }
        flushPara();
    }
    function md(text) {
        var parts = String(text == null ? "" : text).split(/```/);
        var out = "";
        for (var i = 0; i < parts.length; i++) {
            if (i % 2 === 1) {
                var block = parts[i].replace(/^[a-zA-Z0-9_+-]*\n/, "");
                out += "<pre><code>" + esc(block) + "</code></pre>";
            } else {
                var buf = [];
                mdBlock(esc(parts[i]), buf);
                out += buf.join("");
            }
        }
        return out;
    }
    window.md = md;
})();