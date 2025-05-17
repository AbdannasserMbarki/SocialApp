// Update user's last activity every minute
setInterval(updateActivity, 60000);

// Update online users list every 30 seconds
setInterval(updateOnlineUsers, 30000);

function updateActivity() {
    fetch('update_activity.php')
        .catch(error => console.error('Error updating activity:', error));
}

function updateOnlineUsers() {
    fetch('get_online_users.php')
        .then(response => response.json())
        .then(data => {
            const onlineUsersContainer = document.querySelector('.online-users-list');
            if (!onlineUsersContainer) return;

            // Clear current list
            onlineUsersContainer.innerHTML = '';

            if (data.users.length === 0) {
                onlineUsersContainer.innerHTML = '<div class="no-online-users">No users online</div>';
                return;
            }

            // Add each online user to the list
            data.users.forEach(user => {
                const userElement = document.createElement('div');
                userElement.className = 'online-user';
                userElement.innerHTML = `
                    <img src="${user.profile_picture || 'assets/default-avatar.png'}" alt="${user.username}" class="user-avatar">
                    <span class="user-name">${user.username}</span>
                    <span class="online-indicator"></span>
                `;
                onlineUsersContainer.appendChild(userElement);
            });
        })
        .catch(error => console.error('Error fetching online users:', error));
}

// Initial update
document.addEventListener('DOMContentLoaded', () => {
    updateActivity();
    updateOnlineUsers();
}); 