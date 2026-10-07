@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<span class="brand-mark">F</span> <span class="brand-name">{!! $slot !!}</span>
</a>
</td>
</tr>
