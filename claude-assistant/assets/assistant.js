/**
 * Claude AI Virtual Assistant - WordPress Dashboard Frontend
 * Place this file at: /wp-content/plugins/claude-assistant/assets/assistant.js
 *
 * v1.0.3 fixes:
 *  1. Hardened system prompt: Claude must ALWAYS call a tool before answering
 *     any site-specific question — stops hallucination / wrong plugin info.
 *  2. Tool JSON parser now strips accidental markdown fences that Claude
 *     sometimes wraps around tool call responses.
 *  3. Conversation history trimmed to last 20 messages to prevent token bloat
 *     which caused context confusion and hallucinated answers in long chats.
 *  4. Tool result labelled clearly as "LIVE DATA" in history so Claude never
 *     confuses it with user input.
 *  5. Plugin active/inactive status now shown with color-coded visual badges.
 */

(function () {
  "use strict";

  // ── Config ──────────────────────────────────────────────────────────────────
  var ClaudeVA = window.ClaudeVA || {};
  var nonce    = ClaudeVA.nonce    || "";
  var ajaxUrl  = ClaudeVA.ajaxUrl  || "";
  var hasKey   = ClaudeVA.hasKey   || "";
  var siteUrl  = ClaudeVA.siteUrl  || "";
  var siteName = ClaudeVA.siteName || "";
  var apiReady = (hasKey === "yes");

  // ── State ───────────────────────────────────────────────────────────────────
  var conversationHistory = [];
  var isLoading = false;
  var MAX_HISTORY = 20; // FIX 3: cap history to avoid token bloat

  // ── System Prompt ─────────────────────────────────────────────────────────────
  // FIX 1: Much stricter anti-hallucination prompt.
  var SYSTEM_PROMPT =
    "You are a WordPress Virtual Assistant inside the admin dashboard of: " + siteName + " (" + siteUrl + ").\n\n" +

    "=== CRITICAL RULE — READ FIRST ===\n" +
    "You have ZERO prior knowledge about this specific WordPress site.\n" +
    "You do NOT know which plugins are installed, which are active or inactive,\n" +
    "what posts or pages or users exist, or any other site-specific information.\n" +
    "Your general AI training data MUST NOT be used to answer questions about this site.\n" +
    "You MUST call the appropriate tool and receive its result BEFORE answering.\n" +
    "==================================\n\n" +

    "TO CALL A TOOL: respond with ONLY this exact JSON — nothing before it, nothing after:\n" +
    "{\"tool\": \"<action_name>\", \"params\": { ... }}\n\n" +

    "AVAILABLE TOOLS:\n" +
    "list_posts          { count?, status?, type? }\n" +
    "create_post         { title, content, status?, type? }\n" +
    "update_post         { id, title?, content?, status? }\n" +
    "delete_post         { id, force? }\n" +
    "get_post            { id }\n" +
    "list_pages          { count? }\n" +
    "list_users          { count? }\n" +
    "create_user         { username, email, role? }\n" +
    "list_plugins        {}  <- use this for ANY plugin question\n" +
    "activate_plugin     { plugin }  <- use exact file path from list_plugins result\n" +
    "deactivate_plugin   { plugin }\n" +
    "get_site_info       {}\n" +
    "update_option       { key, value }  allowed keys: blogname, blogdescription, admin_email, timezone_string\n" +
    "list_media          { count? }\n" +
    "list_comments       { count?, status? }\n" +
    "approve_comment     { id }\n" +
    "delete_comment      { id }\n" +
    "list_categories     {}\n" +
    "create_category     { name }\n\n" +

    "RULES:\n" +
    "1. ANY question about plugins/posts/users/settings -> call the tool FIRST, answer only after seeing real results.\n" +
    "2. Tool call must be pure JSON only — no markdown fences, no explanation, no text around it.\n" +
    "3. Summarise results using ONLY what the tool returned. Report active/inactive exactly as the data shows.\n" +
    "4. If results are empty, say so — never substitute with assumed or training-data information.\n" +
    "5. For destructive actions (delete/deactivate), confirm with the user first unless explicitly told to proceed.\n" +
    "6. Be concise, accurate, and friendly.";

  // ── Helpers ─────────────────────────────────────────────────────────────────
  function escHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // FIX 2: Strip markdown fences Claude sometimes wraps around JSON tool calls
  function extractToolCall(text) {
    var trimmed = text.trim();
    trimmed = trimmed.replace(/^```(?:json)?\s*/i, "").replace(/\s*```$/, "").trim();
    try {
      var parsed = JSON.parse(trimmed);
      if (parsed && typeof parsed.tool === "string") return parsed;
    } catch (_) {}
    return null;
  }

  // FIX 3: Trim history to prevent token bloat
  function trimmedHistory() {
    if (conversationHistory.length <= MAX_HISTORY) return conversationHistory;
    return [conversationHistory[0]].concat(conversationHistory.slice(-(MAX_HISTORY - 1)));
  }

  // ── DOM Render ──────────────────────────────────────────────────────────────
  var root = document.getElementById("claude-va-root");
  if (!root) return;

  var warningBar = !apiReady
    ? '<div id="cva-warning">&#9888;&#65039; No API key set. <a href="' +
      escHtml(ajaxUrl.replace("admin-ajax.php", "admin.php?page=claude-assistant-settings")) +
      '">Add it in Settings &rarr;</a></div>'
    : "";

  root.innerHTML =
    '<div id="cva-wrap">' +
      '<div id="cva-header">' +
        '<span id="cva-logo">&#9889; Claude Assistant</span>' +
        '<span id="cva-site">' + escHtml(siteName) + '</span>' +
        '<button id="cva-clear" title="Clear chat">&#128465; Clear</button>' +
      '</div>' +
      '<div id="cva-suggestions">' +
        '<span class="cva-chip" data-q="Show me site info">&#128202; Site Info</span>' +
        '<span class="cva-chip" data-q="List my last 5 posts">&#128221; Recent Posts</span>' +
        '<span class="cva-chip" data-q="List all installed plugins and show which ones are active and which are inactive">&#128268; Plugins</span>' +
        '<span class="cva-chip" data-q="Show pending comments">&#128172; Comments</span>' +
        '<span class="cva-chip" data-q="List all users">&#128101; Users</span>' +
        '<span class="cva-chip" data-q="List all categories">&#127991; Categories</span>' +
      '</div>' +
      '<div id="cva-messages"></div>' +
      '<div id="cva-inputbar">' +
        '<textarea id="cva-input" placeholder="Ask me anything about your WordPress site&#8230;" rows="2"></textarea>' +
        '<button id="cva-send">Send</button>' +
      '</div>' +
      warningBar +
    '</div>';

  var msgBox      = document.getElementById("cva-messages");
  var input       = document.getElementById("cva-input");
  var sendBtn     = document.getElementById("cva-send");
  var suggestions = document.getElementById("cva-suggestions");

  // ── Message Rendering ────────────────────────────────────────────────────────
  function addMsg(role, content, extra) {
    extra = extra || "";
    var div = document.createElement("div");
    div.className = "cva-msg cva-" + role;
    var label = role === "user" ? "You" : role === "tool" ? "&#128295; WP Action" : "&#9889; Claude";
    div.innerHTML =
      '<div class="cva-bubble">' +
        "<strong>" + label + "</strong>" +
        '<div class="cva-text">' + content + "</div>" +
        extra +
      "</div>";
    msgBox.appendChild(div);
    msgBox.scrollTop = msgBox.scrollHeight;
    return div;
  }

  function addLoading() {
    return addMsg("assistant", '<span class="cva-dots"><span></span><span></span><span></span></span>');
  }

  // FIX 5: Active/inactive now shown as color-coded badges in the table
  function formatResult(data) {
    if (Array.isArray(data)) {
      if (!data.length) return "<em>No results found.</em>";
      var keys = Object.keys(data[0]);
      var html = '<table class="cva-table"><thead><tr>' +
        keys.map(function(k) { return "<th>" + escHtml(k) + "</th>"; }).join("") +
        "</tr></thead><tbody>";
      data.forEach(function(row) {
        html += "<tr>" + keys.map(function(k) {
          var rawVal = row[k];
          if (k === "active") {
            return rawVal
              ? '<td><span style="color:#16a34a;font-weight:600">&#10003; Active</span></td>'
              : '<td><span style="color:#dc2626;font-weight:600">&#10005; Inactive</span></td>';
          }
          return "<td>" + escHtml(String(rawVal != null ? rawVal : "")) + "</td>";
        }).join("") + "</tr>";
      });
      return html + "</tbody></table>";
    }
    if (typeof data === "object" && data !== null) {
      return Object.entries(data).map(function(pair) {
        return "<div><strong>" + escHtml(pair[0]) + ":</strong> " + escHtml(String(pair[1])) + "</div>";
      }).join("");
    }
    return escHtml(String(data));
  }

  // ── Execute WP Action ────────────────────────────────────────────────────────
  async function executeWPAction(actionType, params) {
    var fd = new FormData();
    fd.append("action", "claude_va_execute");
    fd.append("nonce", nonce);
    fd.append("action_type", actionType);
    fd.append("params", JSON.stringify(params));
    var r = await fetch(ajaxUrl, { method: "POST", body: fd });
    var json = await r.json();
    if (!json.success) throw new Error(json.data || "WP action failed");
    return json.data;
  }

  // ── Call Claude via WP server proxy ─────────────────────────────────────────
  async function callClaude(messages) {
    var fd = new FormData();
    fd.append("action", "claude_va_chat");
    fd.append("nonce", nonce);
    fd.append("messages", JSON.stringify(messages));
    fd.append("system", SYSTEM_PROMPT);
    var r = await fetch(ajaxUrl, { method: "POST", body: fd });
    var json = await r.json();
    if (!json.success) throw new Error(json.data || "Claude API error");
    return (json.data.content && json.data.content[0] && json.data.content[0].text) || "";
  }

  // ── Main Chat Handler ─────────────────────────────────────────────────────────
  async function handleSend() {
    var userText = input.value.trim();
    if (!userText || isLoading || !apiReady) return;

    isLoading = true;
    sendBtn.disabled = true;
    input.value = "";
    if (suggestions) suggestions.style.display = "none";

    addMsg("user", escHtml(userText));
    conversationHistory.push({ role: "user", content: userText });

    var loadEl = addLoading();
    var loadElRemoved = false;

    function safeRemoveLoad() {
      if (!loadElRemoved && loadEl && loadEl.parentNode) {
        loadEl.remove();
        loadElRemoved = true;
      }
    }

    try {
      var assistantReply = await callClaude(trimmedHistory()); // FIX 3
      var iterations = 0;

      while (iterations < 6) {
        iterations++;

        var toolCall = extractToolCall(assistantReply); // FIX 2
        if (!toolCall) break;

        safeRemoveLoad();

        var toolResultData, toolError;
        try {
          toolResultData = await executeWPAction(toolCall.tool, toolCall.params || {});
        } catch (err) {
          toolError = err.message;
        }

        var resultHtml = toolError
          ? '<span class="cva-err">Error: ' + escHtml(toolError) + "</span>"
          : formatResult(toolResultData);

        addMsg("tool", escHtml(toolCall.tool), resultHtml);

        var toolResultText = toolError
          ? "Tool error: " + toolError
          : JSON.stringify(toolResultData, null, 2);

        conversationHistory.push({ role: "assistant", content: assistantReply });
        // FIX 4: Clearly label as live data so Claude doesn't confuse it with user input
        conversationHistory.push({
          role: "user",
          content: "LIVE TOOL RESULT for \"" + toolCall.tool + "\" (real data from this WordPress database — use only this):\n" + toolResultText
        });

        loadEl = addLoading();
        loadElRemoved = false;
        assistantReply = await callClaude(trimmedHistory());
      }

      safeRemoveLoad();
      addMsg("assistant", escHtml(assistantReply).replace(/\n/g, "<br>"));
      conversationHistory.push({ role: "assistant", content: assistantReply });

    } catch (err) {
      safeRemoveLoad();
      addMsg("assistant", '<span class="cva-err">Error: ' + escHtml(err.message) + "</span>");
    }

    isLoading = false;
    sendBtn.disabled = false;
    input.focus();
  }

  // ── Events ───────────────────────────────────────────────────────────────────
  sendBtn.addEventListener("click", handleSend);
  input.addEventListener("keydown", function(e) {
    if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); handleSend(); }
  });
  document.getElementById("cva-clear").addEventListener("click", function() {
    conversationHistory = [];
    msgBox.innerHTML = "";
    if (suggestions) suggestions.style.display = "flex";
  });
  document.querySelectorAll(".cva-chip").forEach(function(chip) {
    chip.addEventListener("click", function() {
      input.value = chip.dataset.q;
      handleSend();
    });
  });

  // ── Welcome ──────────────────────────────────────────────────────────────────
  addMsg("assistant",
    "Hello! I&#8217;m your Claude AI assistant for <strong>" + escHtml(siteName) + "</strong>.<br>" +
    "I always fetch live data from your site before answering &#8212; no guessing. Just ask!"
  );

})();