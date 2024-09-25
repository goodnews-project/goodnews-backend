<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>重置密码</title>
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'SF Pro SC', 'HanHei SC', 'SF Pro Text', 'Myriad Set Pro', 'SF Pro Icons', 'Apple Legacy Chevron', 'PingFang SC', 'Helvetica Neue',
          'Helvetica', 'Arial', sans-serif;
      }
      .app {
        background: #fafafa;
        width: 100%;
        height: 100vh;
        padding: 20px;
      }
      .photo {
        width: 300px;
        max-width: 60%;
        margin: 0 auto;
        padding-top: 100px;
      }
      .photo img {
        width: 100%;
      }
      .frame {
        max-width: 100%;
        width: 500px;
        background: #fff;
        box-shadow: 0px 10px 50px 0px #00000014;
        border: 1px solid #eeeeee;
        padding: 40px;
        gap: 20px;
        border-radius: 10px;
        border: 1px solid #eeeeee;
        margin: 40px auto;
        color: #000;
      }

      .frame .title {
        font-size: 24px;
        text-align: center;
        font-weight: 500;
        margin-bottom: 20px;
      }
      .frame .desc {
        font-weight: 400;
        margin-bottom: 20px;
      }
      .frame .btn {
        display: inline-block;
        width: 100%;
        text-align: center;
        padding: 14px;
        background: #007aff;
        color: #fff;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        margin-bottom: 20px;
        text-decoration: none;
      }
      .frame .footer-desc {
        font-size: 0;
        color: #000;
        font-weight: 400;
      }
      .frame .footer-desc span {
        font-size: 14px;
      }
      .frame .footer-desc a {
        font-size: 14px;
        text-decoration: underline;
        cursor: pointer;
        color: #000;
      }
      .frame .footer-desc a:hover {
        color: #007aff;
      }
    </style>
  </head>
  <body>
    <div class="app">
      <div class="main">
        <div class="photo">
          <img src="https://file.aaaaa.bet/remote/origin/2024-06-13/9I9zbhgTE7wFrkNNoq66.png" alt="" />
        </div>
        <div class="frame">
          <div class="title">重置密码</div>
          <div class="desc">点击下面的链接来更改账户的密码。</div>
          <a href="{{ $link }}" class="btn">更改密码</a>
          <div class="footer-desc">
            <span>如果你并没有请求本次变更，请忽略此邮件。你的密码只有在你点击上面的链接并输入新密码后才会更改。</span>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>