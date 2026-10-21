const listView = document.getElementById("list-view");
const postView = document.getElementById("post-view");

const postList = document.getElementById("post-list");
const emptyMessage = document.getElementById("empty-message");
const boardMessage = document.getElementById("board-message");

const postForm = document.getElementById("post-form");
const replyForm = document.getElementById("reply-form");

let currentPostId = null;

function showMessage(message, isError = false) {
    boardMessage.textContent = message;
    boardMessage.style.color = isError ? "#dc2626" : "#16a34a";
}

function formatDate(value) {
    const date = new Date(String(value).replace(" ", "T"));
    return isNaN(date) ? value : date.toLocaleString();
}

async function api(action, fields = {}) {
    let response;
    try {
        response = await fetch("discussion.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action, ...fields })
        });
    } catch (error) {
        console.error("Discussion request failed:", error);
        return { success: false, message: "Unable to reach the discussion service." };
    }

    if (response.status === 401) {
        window.location.href = "index.html";
        return { success: false, message: "Please log in." };
    }

    try {
        return await response.json();
    } catch (error) {
        return { success: false, message: "Unexpected response from the server." };
    }
}

function showList() {
    postView.hidden = true;
    listView.hidden = false;
    currentPostId = null;
    loadPosts();
}

function showPost(postId) {
    currentPostId = postId;
    listView.hidden = true;
    postView.hidden = false;
    loadPost(postId);
}

async function loadPosts() {
    showMessage("");
    const data = await api("list_posts");

    if (!data.success) {
        showMessage(data.message || "Could not load posts.", true);
        return;
    }

    postList.replaceChildren();
    emptyMessage.hidden = data.posts.length > 0;

    data.posts.forEach(function (post) {
        const item = document.createElement("li");
        item.className = "post-item";

        const title = document.createElement("button");
        title.type = "button";
        title.className = "post-title";
        title.textContent = post.title;
        title.addEventListener("click", function () { showPost(post.id); });

        const meta = document.createElement("p");
        meta.className = "meta";
        const replies = Number(post.reply_count);
        meta.textContent = "Posted by " + post.username + " on " + formatDate(post.created_at) +
            " - " + replies + (replies === 1 ? " reply" : " replies");

        item.append(title, meta);
        postList.appendChild(item);
    });
}

async function loadPost(postId) {
    showMessage("");
    const data = await api("get_post", { postId });

    if (!data.success) {
        showMessage(data.message || "Could not load that post.", true);
        showList();
        return;
    }

    document.getElementById("post-view-title").textContent = data.post.title;
    document.getElementById("post-view-meta").textContent =
        "Posted by " + data.post.username + " on " + formatDate(data.post.created_at);
    document.getElementById("post-view-body").textContent = data.post.body;

    const replyList = document.getElementById("reply-list");
    replyList.replaceChildren();

    document.getElementById("replies-heading").textContent =
        data.replies.length === 1 ? "1 reply" : data.replies.length + " replies";

    data.replies.forEach(function (reply) {
        const item = document.createElement("li");
        item.className = "reply-item";

        const meta = document.createElement("p");
        meta.className = "meta";
        meta.textContent = reply.username + " replied on " + formatDate(reply.created_at);

        const body = document.createElement("p");
        body.className = "body-text";
        body.textContent = reply.body;

        item.append(meta, body);
        replyList.appendChild(item);
    });
}

document.getElementById("new-post-button").addEventListener("click", function () {
    postForm.hidden = false;
    document.getElementById("post-title").focus();
});

document.getElementById("cancel-post").addEventListener("click", function () {
    postForm.hidden = true;
    postForm.reset();
});

postForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const title = document.getElementById("post-title").value.trim();
    const body = document.getElementById("post-body").value.trim();

    if (!title || !body) {
        showMessage("Please add a title and a description.", true);
        return;
    }

    const data = await api("create_post", { title, body });

    if (data.success) {
        postForm.reset();
        postForm.hidden = true;
        showMessage("Post created.");
        loadPosts();
    } else {
        showMessage(data.message || "Could not create the post.", true);
    }
});

replyForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const body = document.getElementById("reply-body").value.trim();
    if (!body) {
        showMessage("Please write a reply first.", true);
        return;
    }

    const data = await api("create_reply", { postId: currentPostId, body });

    if (data.success) {
        replyForm.reset();
        showMessage("Reply posted.");
        loadPost(currentPostId);
    } else {
        showMessage(data.message || "Could not post the reply.", true);
    }
});

document.getElementById("back-button").addEventListener("click", showList);

loadPosts();
