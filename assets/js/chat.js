const webSocket = new WebSocket('ws://localhost:2346');
const messageBox = document.getElementById('messageBox');
const messageForm = document.getElementById('messageForm');

function escapeHTML(html) {
  var escape = document.createElement('textarea');
  escape.textContent = html;
  return escape.innerHTML;
}

function scroll() {
  document.body.scrollIntoView(false);
}

function formatDate(date) {
  const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
  return new Date(date).toLocaleDateString('en-US', options);
}


function formatDateSeparator(date) {
  try {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    date.setHours(0, 0, 0, 0);
    const interval = Math.floor((now - date) / (1000 * 60 * 60 * 24));

    if (interval === 0) {
      return "Сегодня";
    } else if (interval === 1) {
      return "Вчера";
    } else if (interval === 2) {
      return "Позавчера";
    } else if (interval > 2 && interval <= 7) {
      const dayOfWeek = translateDayOfWeek(date.getDay());
      return dayOfWeek;
    } else {
      return formatDate(date) + ' ' + formatTime(date);
    }
  } catch (error) {
    console.error('Error formatting date:', error);
    return 'Invalid Date';
  }
}
function updateChatUI(messages) {
  console.log('All Messages:', messages);

  if (!Array.isArray(messages)) {
    console.error('Invalid format for messages:', messages);
    return;
  }

  messages.forEach((msg) => {
    const isMyMessage = msg.sender_id == user_id;
    const messageClass = isMyMessage ? 'my-message' : 'companion-message';

    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${messageClass}`;
    messageDiv.innerHTML = `
      <span class="message-msg">${escapeHTML(msg.message_text)}</span>
      <div class="message-info">
        <span>${formatDateSeparator(new Date(msg.date))} ${formatTime(new Date(msg.time))}</span>
        ${isMyMessage ? `<span>${msg.read_status == 1 ? '<i class="fa-solid fa-check-double"></i>' : '<i class="fa-solid fa-check"></i>'}</span>` : ''}
      </div>
    `;

    messageBox.appendChild(messageDiv);
  });

  scroll();
}




function formatTime(date) {
  const hours = date.getHours().toString().padStart(2, '0');
  const minutes = date.getMinutes().toString().padStart(2, '0');
  return `${hours}:${minutes}`;
}

function translateDayOfWeek(day) {
  var days = [
    'В Воскресенье',
    'В Понедельник',
    'Во Вторник',
    'В Среду',
    'В Четверг',
    'В Пятницу',
    'В Субботу'
  ];

  return days[day];
}


webSocket.addEventListener('open', async function (event) {
  console.log('WebSocket Connection Opened');

  let data = {
    user_id: user_id,
    receiver_id: receiverId,
    load_all: true
  };

  webSocket.send(JSON.stringify(data));
});


function processUnreadMessages(unreadMessages) {
  const allMessages = [...unreadMessages, ...existingMessages]; 
  updateChatUI(allMessages);
}

// webSocket.addEventListener('message', async function (event) {
//   try {
//     const message = JSON.parse(event.data);

//     if (message.all_messages || message.new_messages || message.unread_messages) {
//       const allMessages = message.all_messages || [];
//       const newMessages = message.new_messages || [];
//       const unreadMessages = message.unread_messages || [];

//       const combinedMessages = [...allMessages, ...newMessages];

//       updateChatUI(combinedMessages);

//       processUnreadMessages(unreadMessages);
//     } else {
//       console.error('Invalid message format:', event.data);
//     }
//   } catch (error) {
//     // console.error('Error processing message:', error); 
//   }
// });
webSocket.addEventListener('message', (event) => {
  const data = JSON.parse(event.data);

  if (data.new_messages && data.new_messages.length > 0) {
    // Log new messages to the browser console
    console.log('New Messages:', data.new_messages);
  }

  if (data.all_messages && data.all_messages.length > 0) {
    // Log all messages to the browser console
    console.log('All Messages:', data.all_messages);
  }
});


messageForm.addEventListener('submit', async function (event) {
  event.preventDefault();
  const messageText = escapeHTML(document.getElementById('message_text').value);

  const data = {
    user_id: user_id,
    receiver_id: receiverId,
    message_text: messageText
  };

  webSocket.send(JSON.stringify(data));
  document.getElementById('message_text').value = '';
});