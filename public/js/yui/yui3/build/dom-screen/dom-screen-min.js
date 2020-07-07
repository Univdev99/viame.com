/*
YUI 3.18.1 (build f7e7bcb)
Copyright 2014 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("dom-screen",function(e,t){(function(e){var t="documentElement",n="compatMode",r="position",i="fixed",s="relative",o="left",u="top",a="BackCompat",f="medium",l="borderLeftWidth",c="borderTopWidth",h="getBoundingClientRect",p="getComputedStyle",d=e.DOM,v=/^t(?:able|d|h)$/i,m;e.UA.ie&&(e.config.doc[n]!=="BackCompat"?m=t:m="body"),e.mix(d,{winHeight:function(e){var t=d._getWinSize(e).height;return t},winWidth:function(e){var t=d._getWinSize(e).width;return t},docHeight:function(e){var t=d._getDocSize(e).height;return Math.max(t,d._getWinSize(e).height)},docWidth:function(e){var t=d._getDocSize(e).width;return Math.max(t,d._getWinSize(e).width)},docScrollX:function(n,r){r=r||n?d._getDoc(n):e.config.doc;var i=r.defaultView,s=i?i.pageXOffset:0;return Math.max(r[t].scrollLeft,r.body.scrollLeft,s)},docScrollY:function(n,r){r=r||n?d._getDoc(n):e.config.doc;var i=r.defaultView,s=i?i.pageYOffset:0;return Math.max(r[t].scrollTop,r.body.scrollTop,s)},getXY:function(){return e.config.doc[t][h]?function(r){var i=null,s,o,u,f,l,c,p,v,g,y;if(r&&r.tagName){p=r.ownerDocument,u=p[n],u!==a?y=p[t]:y=p.body,y.contains?g=y.contains(r):g=e.DOM.contains(y,r);if(g){v=p.defaultView,v&&"pageXOffset"in v?(s=v.pageXOffset,o=v.pageYOffset):(s=m?p[m].scrollLeft:d.docScrollX(r,p),o=m?p[m].scrollTop:d.docScrollY(r,p)),e.UA.ie&&(!p.documentMode||p.documentMode<8||u===a)&&(l=y.clientLeft,c=y.clientTop),f=r[h](),i=[f.left,f.top];if(l||c)i[0]-=l,i[1]-=c;if(o||s)if(!e.UA.ios||e.UA.ios>=4.2)i[0]+=s,i[1]+=o}else i=d._getOffset(r)}return i}:function(t){var n=null,s,o,u,a,f;if(t)if(d.inDoc(t)){n=[t.offsetLeft,t.offsetTop],s=t.ownerDocument,o=t,u=e.UA.gecko||e.UA.webkit>519?!0:!1;while(o=o.offsetParent)n[0]+=o.offsetLeft,n[1]+=o.offsetTop,u&&(n=d._calcBorders(o,n));if(d.getStyle(t,r)!=i){o=t;while(o=o.parentNode){a=o.scrollTop,f=o.scrollLeft,e.UA.gecko&&d.getStyle(o,"overflow")!=="visible"&&(n=d._calcBorders(o,n));if(a||f)n[0]-=f,n[1]-=a}n[0]+=d.docScrollX(t,s),n[1]+=d.docScrollY(t,s)}else n[0]+=d.docScrollX(t,s),n[1]+=d.docScrollY(t,s)}else n=d._getOffset(t);return n}}(),getScrollbarWidth:e.cached(function(){var t=e.config.doc,n=t.createElement("div"),r=t.getElementsByTagName("body")[0],i=.1;return r&&(n.style.cssText="position:absolute;visibility:hidden;overflow:scroll;width:20px;",n.appendChild(t.createElement("p")).style.height="1px",r.insertBefore(n,r.firstChild),i=n.offsetWidth-n.clientWidth,r.removeChild(n)),i},null,.1),getX:function(e){return d.getXY(e)[0]},getY:function(e){return d.getXY(e)[1]},setXY:function(e,t,n){var i=d.setStyle,a,f,l,c;e&&t&&(a=d.getStyle(e,r),f=d._getOffset(e),a=="static"&&(a=s,i(e,r,a)),c=d.getXY(e),t[0]!==null&&i(e,o,t[0]-c[0]+f[0]+"px"),t[1]!==null&&i(e,u,t[1]-c[1]+f[1]+"px"),n||(l=d.getXY(e),(l[0]!==t[0]||l[1]!==t[1])&&d.setXY(e,t,!0)))},setX:function(e,t){return d.setXY(e,[t,null])},setY:function(e,t){return d.setXY(e,[null,t])},swapXY:function(e,t){var n=d.getXY(e);d.setXY(e,d.getXY(t)),d.setXY(t,n)},_calcBorders:function(t,n){var r=parseInt(d[p](t,c),10)||0,i=parseInt(d[p](t,l),10)||0;return e.UA.gecko&&v.test(t.tagName)&&(r=0,i=0),n[0]+=i,n[1]+=r,n},_getWinSize:function(r,i){i=i||r?d._getDoc(r):e.config.doc;var s=i.defaultView||i.parentWindow,o=i[n],u=s.innerHeight,a=s.innerWidth,f=i[t];return o&&!e.UA.opera&&(o!="CSS1Compat"&&(f=i.body),u=f.clientHeight,a=f.clientWidth),{height:u,width:a}},_getDocSize:function(r){var i=r?d._getDoc(r):e.config.doc,s=i[t];return i[n]!="CSS1Compat"&&(s=i.body),{height:s.scrollHeight,width:s.scrollWidth}}})})(e),function(e){var t="top",n="right",r="bottom",i="left",s=function(e,s){var o=Math.max(e[t],s[t]),u=Math.min(e[n],s[n]),a=Math.min(e[r],s[r]),f=Math.max(e[i],s[i]),l={};return l[t]=o,l[n]=u,l[r]=a,l[i]=f,l},o=e.DOM;e.mix(o,{region:function(e){var t=o.getXY(e),n=!1;return e&&t&&(n=o._getRegion(t[1],t[0]+e.offsetWidth,t[1]+e.offsetHeight,t[0])),n},intersect:function(u,a,f){var l=f||o.region(u),c={},h=a,p;if(h.tagName)c=o.region(h);else{if(!e.Lang.isObject(a))return!1;c=a}return p=s(c,l),{top:p[t],right:p[n],bottom:p[r],left:p[i],area:(p[r]-p[t])*(p[n]-p[i]),yoff:p[r]-p[t],xoff:p[n]-p[i],inRegion:o.inRegion(u,a,!1,f)}},inRegion:function(u,a,f,l){var c={},h=l||o.region(u),p=a,d;if(p.tagName)c=o.region(p);else{if(!e.Lang.isObject(a))return!1;c=a}return f?h[i]>=c[i]&&h[n]<=c[n]&&h[t]>=c[t]&&h[r]<=c[r]:(d=s(c,h),d[r]>=d[t]&&d[n]>=d[i]?!0:!1)},inViewportRegion:function(e,t,n){return o.inRegion(e,o.viewportRegion(e),t,n)},_getRegion:function(e,s,o,u){var a={};return a[t]=a[1]=e,a[i]=a[0]=u,a[r]=o,a[n]=s,a.width=a[n]-a[i],a.height=a[r]-a[t],a},viewportRegion:function(t){t=t||e.config.doc.documentElement;var n=!1,r,i;return t&&(r=o.docScrollX(t),i=o.docScrollY(t),n=o._getRegion(i,o.winWidth(t)+r,i+o.winHeight(t),r)),n}})}(e)},"3.18.1",{requires:["dom-base","dom-style"]});
