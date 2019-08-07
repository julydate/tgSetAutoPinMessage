# tgSetAutoPinMessage
> Telegram 自动恢复群组被绑定频道更改的置顶消息
   
Telegram群组和频道绑定过后，频道发布的消息会自动同步到群组并置顶
   
使用本机器人，可以自动恢复群组被绑定频道更改后的置顶消息为之前人工置顶的消息，亦可自动删除频道转发过来的置顶消息
   
使用前请先保证机器人拥有群组管理员权限（删除消息和置顶消息的权限）
   
使用命令如下（只有群组管理员可以进行更改，默认状态下为关闭）：
```
setAutoPinMessage - 自动恢复置顶消息 on 为开启 off 为关闭
setAutoDelPinMessage - 自动删除频道置顶消息 on 为开启 off 为关闭
```
设置 Telegram Bot WebHook:
```
https://api.telegram.org/bot$token/setWebhook?url=https://example.com/channelBot.php?token=$token
```
另外需要在本程序同目录下创建 channelbotconfig 文件夹，并在该文件夹里创建一个 config.json 文件
