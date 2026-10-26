@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
<tr>
<td style="background-color: #0f766e; border-radius: 6px; color: #ffffff; font-size: 18px; font-weight: bold; height: 32px; line-height: 32px; text-align: center; width: 32px;">+</td>
<td style="color: #0f172a; font-size: 19px; font-weight: bold; padding-left: 10px;">{!! $slot !!}</td>
</tr>
</table>
</a>
</td>
</tr>
