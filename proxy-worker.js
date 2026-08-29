/**
 * Direct-Download Proxy — Cloudflare Worker
 * -------------------------------------------------
 * এই ফাইলটা Cloudflare Workers-এ ডিপ্লয় করলে আপনার
 * অ্যাপের "Download Proxy URL" সেটিংসে বসাতে পারবেন।
 *
 * কাজ কী করে:
 *  - আপনার অ্যাপ থেকে আসা ?url=... এর ভিডিও লিংকটা
 *    এই ওয়ার্কার সার্ভার-সাইডে fetch করে (এখানে কোনো
 *    CORS বাধা থাকে না, কারণ এটা browser না, সার্ভার)
 *  - রেসপন্সে Content-Disposition: attachment হেডার
 *    জুড়ে দেয়, ফলে যেকোনো ব্রাউজার এটাকে ভিডিও হিসেবে
 *    "প্লে" না করে সরাসরি "ডাউনলোড" হিসেবে ট্রিট করে
 *  - সেই সাথে Access-Control-Allow-Origin: * হেডার
 *    দেয়, তাই আপনার অ্যাপের fetch() কলটাও ব্লক হয় না
 *
 * ডিপ্লয় করার ধাপ (ফ্রি, কার্ড লাগে না):
 *  1) https://workers.cloudflare.com এ গিয়ে সাইন আপ করুন
 *  2) "Create Worker" চাপুন, একটা নাম দিন (যেমন: dl-proxy)
 *  3) এডিটরে ডিফল্ট কোড মুছে এই পুরো ফাইলের কোড পেস্ট করুন
 *  4) "Deploy" চাপুন — একটা URL পাবেন, যেমন:
 *     https://dl-proxy.<আপনার-নাম>.workers.dev
 *  5) সেই URL-টা অ্যাপের Settings > Download Proxy URL
 *     ফিল্ডে বসিয়ে দিন
 */

export default {
  async fetch(request) {
    const requestUrl = new URL(request.url);
    const targetUrl = requestUrl.searchParams.get('url');

    // সাধারণ CORS preflight (OPTIONS) রিকোয়েস্ট সামলানো
    if (request.method === 'OPTIONS') {
      return new Response(null, { headers: corsHeaders() });
    }

    if (!targetUrl) {
      return new Response('Missing ?url= parameter', {
        status: 400,
        headers: corsHeaders(),
      });
    }

    // শুধু সাধারণ http/https লিংক allow করা হচ্ছে
    let parsedTarget;
    try {
      parsedTarget = new URL(targetUrl);
      if (!['http:', 'https:'].includes(parsedTarget.protocol)) {
        throw new Error('Invalid protocol');
      }
    } catch (err) {
      return new Response('Invalid url parameter', {
        status: 400,
        headers: corsHeaders(),
      });
    }

    try {
      const upstreamResponse = await fetch(targetUrl, {
        headers: {
          // অনেক ভিডিও CDN রেঞ্জ-রিকোয়েস্ট আশা করে
          Range: request.headers.get('Range') || undefined,
          'User-Agent':
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' +
            'AppleWebKit/537.36 (KHTML, like Gecko) ' +
            'Chrome/124.0.0.0 Safari/537.36',
        },
      });

      if (!upstreamResponse.ok && upstreamResponse.status !== 206) {
        return new Response(
          `Upstream error: ${upstreamResponse.status}`,
          { status: upstreamResponse.status, headers: corsHeaders() }
        );
      }

      // ফাইলের নাম বের করার চেষ্টা, না পেলে ডিফল্ট নাম
      const filename =
        requestUrl.searchParams.get('filename') ||
        parsedTarget.pathname.split('/').pop() ||
        'download';

      const headers = new Headers(upstreamResponse.headers);
      headers.set(
        'Content-Disposition',
        `attachment; filename="${filename}"`
      );
      Object.entries(corsHeaders()).forEach(([k, v]) =>
        headers.set(k, v)
      );

      return new Response(upstreamResponse.body, {
        status: upstreamResponse.status,
        headers,
      });
    } catch (err) {
      return new Response(`Proxy fetch failed: ${err.message}`, {
        status: 502,
        headers: corsHeaders(),
      });
    }
  },
};

function corsHeaders() {
  return {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, OPTIONS',
    'Access-Control-Allow-Headers': 'Range, Content-Type',
  };
}
