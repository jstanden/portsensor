<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset={$smarty.const.LANG_CHARSET_CODE}">
  <META HTTP-EQUIV="Cache-Control" CONTENT="no-cache">

  <title>{$settings->get('portsensor.core','app_title')}</title>
  <link type="image/x-icon" rel="shortcut icon" href="{devblocks_url}favicon.ico{/devblocks_url}">
  
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=portsensor.core&f=css/jquery-ui-1.7.2.custom.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">
  <link type="text/css" rel="stylesheet" href="{devblocks_url}c=resource&p=portsensor.core&f=css/portsensor.css{/devblocks_url}?v={$smarty.const.APP_BUILD}">

  <!-- Production -->
  <script language="javascript" type="text/javascript" src="{devblocks_url}c=resource&p=portsensor.core&f=js/jquery/jquery.combined.min.js{/devblocks_url}?v={$smarty.const.APP_BUILD}"></script>

  <!-- [TODO] Cache this -->
  <script language="javascript" type="text/javascript">
    {include file="libs/devblocks/api/devblocks.tpl.js"}
  </script>
</head>

<body>
