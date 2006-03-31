<!-- AGENT 'header' title=a$title -->

{a$message}

<!-- IF a$time > 0 -->
<script type="text/javascript"><!--//
R = {a$redirect|js};
setTimeout("location.replace(R)", {a$time*1000})
//--></script><meta http-equiv="refresh" content="{a$time}; URL={a$redirect}" />
<!-- END:IF -->

<!-- AGENT 'footer' -->
