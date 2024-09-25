<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>注册确认</title>
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
          <div class="title">确认电子邮件地址</div>
          <div class="desc">你已经用这个电子邮件地址创建了一个帐户。你只需要点击一下就可以激活它。如果不是你，请忽略这封邮件</div>
          <a href="{{ $link }}" class="btn">确认并返回到 {{ $domain }}</a>
          <div class="footer-desc">
            <span>请同时查看</span>
            <a href="javascript:void(0);">服务器的规则</a>
            <span>和我们的</span>
            <a href="javascript:void(0);">服务条款</a>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>