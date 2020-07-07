<?php
/*
ViaMe Application
Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Vm_ValidateEmail
{
    /**
    * PHP Socket resource to remote MTA
    * @var resource $sock
    */
    var $sock;

    /**
    * Current User being validated
    */
    var $user;
    
    /**
    * Current domain where user is being validated
    */
    var $domain;
    
    /**
    * List of domains to validate users on
    */
    var $domains;
    
    /**
    * SMTP Port
    */
    var $port = 25;
    
    /**
    * Maximum Connection Time to wait for connection establishment per MTA
    */
    var $max_conn_time = 20;
    
    /**
    * Maximum time to read from socket before giving up
    */
    var $max_read_time = 5;

    /**
    * username of sender
    */
    var $from_user = 'noreply';
    
    /**
    * Host Name of sender
    */
    var $from_domain = 'viame.com';

    /**
    * Nameservers to use when make DNS query for MX entries
    * @var Array $nameservers
    * https://github.com/martenson/disposable-email-domains
    */
    var $deaDomains = array(
        /* These are from martenson - Updated 7/20/2016 */
        '0-mail.com', '027168.com', '0815.ru', '0815.ry', '0815.su', '0845.ru', '0clickemail.com', '0wnd.net', '0wnd.org', '0x207.info', '1-8.biz', '100likers.com', '10mail.com', '10mail.org', '10minut.com.pl', '10minutemail.cf', '10minutemail.co.uk', '10minutemail.co.za', '10minutemail.com', '10minutemail.de', '10minutemail.ga', '10minutemail.gq', '10minutemail.ml', '10minutemail.net', '10minutesmail.com', '10x9.com', '123-m.com', '12houremail.com', '12minutemail.com', '12minutemail.net', '140unichars.com', '147.cl', '14n.co.uk', '1ce.us', '1chuan.com', '1fsdfdsfsdf.tk', '1mail.ml', '1pad.de', '1st-forms.com', '1to1mail.org', '1zhuan.com', '20email.eu', '20email.it', '20mail.in', '20mail.it', '20minutemail.com', '2120001.net', '21cn.com', '24hourmail.com', '24hourmail.net', '2fdgdfgdfgdf.tk', '2prong.com', '30minutemail.com', '33mail.com', '36ru.com', '3d-painting.com', '3l6.com', '3mail.ga', '3trtretgfrfe.tk', '4-n.us', '418.dk', '4gfdsgfdgfd.tk', '4mail.cf', '4mail.ga', '4warding.com', '4warding.net', '4warding.org', '5ghgfhfghfgh.tk', '5gramos.com', '5mail.cf', '5mail.ga', '5oz.ru', '5x25.com', '60minutemail.com', '672643.net', '675hosting.com', '675hosting.net', '675hosting.org', '6hjgjhgkilkj.tk', '6ip.us', '6mail.cf', '6mail.ga', '6mail.ml', '6paq.com', '6url.com', '75hosting.com', '75hosting.net', '75hosting.org', '7days-printing.com', '7mail.ga', '7mail.ml', '7tags.com', '80665.com', '8127ep.com', '8mail.cf', '8mail.ga', '8mail.ml', '99experts.com', '9mail.cf', '9ox.net', 'a-bc.net', 'a.asu.mx', 'a.betr.co', 'a.mailcker.com', 'a.vztc.com', 'a45.in', 'abakiss.com', 'abcmail.email', 'abusemail.de', 'abyssmail.com', 'ac20mail.in', 'academiccommunity.com', 'acentri.com', 'add3000.pp.ua', 'adobeccepdm.com', 'adpugh.org', 'adsd.org', 'advantimo.com', 'adwaterandstir.com', 'aegia.net', 'aegiscorp.net', 'aeonpsi.com', 'afrobacon.com', 'ag.us.to', 'agedmail.com', 'agtx.net', 'ahk.jp', 'ajaxapp.net', 'akapost.com', 'akerd.com', 'al-qaeda.us', 'aligamel.com', 'alisongamel.com', 'alivance.com', 'alldirectbuy.com', 'allen.nom.za', 'allthegoodnamesaretaken.org', 'alph.wtf', 'ama-trade.de', 'ama-trans.de', 'amail.com', 'amail4.me', 'amazon-aws.org', 'amelabs.com', 'amilegit.com', 'amiri.net', 'amiriindustries.com', 'ampsylike.com', 'an.id.au', 'anappfor.com', 'anappthat.com', 'andthen.us', 'animesos.com', 'ano-mail.net', 'anon-mail.de', 'anonbox.net', 'anonmails.de', 'anonymail.dk', 'anonymbox.com', 'anonymized.org', 'anonymousness.com', 'ansibleemail.com', 'anthony-junkmail.com', 'antireg.com', 'antireg.ru', 'antispam.de', 'antispam24.de', 'antispammail.de', 'apfelkorps.de', 'aphlog.com', 'appc.se', 'appinventor.nl', 'appixie.com', 'armyspy.com', 'aron.us', 'arroisijewellery.com', 'artman-conception.com', 'arvato-community.de', 'aschenbrandt.net', 'asdasd.nl', 'asdasd.ru', 'ashleyandrew.com', 'ass.pp.ua', 'astroempires.info', 'at0mik.org', 'atvclub.msk.ru', 'augmentationtechnology.com', 'auti.st', 'autorobotica.com', 'autotwollow.com', 'aver.com', 'axiz.org', 'azcomputerworks.com', 'azmeil.tk', 'b.kyal.pl', 'b1of96u.com', 'b2cmail.de', 'badgerland.eu', 'badoop.com', 'barryogorman.com', 'basscode.org', 'bauwerke-online.com', 'baxomale.ht.cx', 'bazaaboom.com', 'bcast.ws', 'bccto.me', 'bearsarefuzzy.com', 'beddly.com', 'beefmilk.com', 'belljonestax.com', 'benipaula.org', 'bestchoiceusedcar.com', 'bidourlnks.com', 'big1.us', 'bigprofessor.so', 'bigstring.com', 'bigwhoop.co.za', 'binkmail.com', 'bio-muesli.info', 'bio-muesli.net', 'blackmarket.to', 'bladesmail.net', 'blip.ch', 'blogmyway.org', 'bluedumpling.info', 'bluewerks.com', 'bobmail.info', 'bobmurchison.com', 'bodhi.lawlita.com', 'bofthew.com', 'bonobo.email', 'bookthemmore.com', 'bootybay.de', 'borged.com', 'borged.net', 'borged.org', 'boun.cr', 'bouncr.com', 'boxformail.in', 'boximail.com', 'boxtemp.com.br', 'br.mintemail.com', 'brandallday.net', 'breakthru.com', 'brefmail.com', 'brennendesreich.de', 'briggsmarcus.com', 'broadbandninja.com', 'bsnow.net', 'bspamfree.org', 'bspooky.com', 'bst-72.com', 'btb-notes.com', 'btc.email', 'bu.mintemail.com', 'buffemail.com', 'bugmenever.com', 'bugmenot.com', 'bulrushpress.com', 'bum.net', 'bumpymail.com', 'bunchofidiots.com', 'bund.us', 'bundes-li.ga', 'bunsenhoneydew.com', 'burnthespam.info', 'burstmail.info', 'businessbackend.com', 'businesssuccessislifesuccess.com', 'buspad.org', 'buymoreplays.com', 'buyordie.info', 'buyusedlibrarybooks.org', 'byebyemail.com', 'byespm.com', 'byom.de', 'c.lain.ch', 'c2.hu', 'c51vsgq.com', 'cachedot.net', 'californiafitnessdeals.com', 'cam4you.cc', 'card.zp.ua', 'casualdx.com', 'cbair.com', 'cc.liamria', 'ce.mintemail.com', 'cek.pm', 'cellurl.com', 'centermail.com', 'centermail.net', 'chacuo.net', 'chammy.info', 'cheatmail.de', 'chielo.com', 'childsavetrust.org', 'chilkat.com', 'chithinh.com', 'chogmail.com', 'choicemail1.com', 'chong-mail.com', 'chong-mail.net', 'chong-mail.org', 'chumpstakingdumps.com', 'cigar-auctions.com', 'ckiso.com', 'cl-cl.org', 'cl0ne.net', 'clandest.in', 'clipmail.eu', 'clixser.com', 'clrmail.com', 'cmail.com', 'cmail.net', 'cmail.org', 'cnamed.com', 'cnmsg.net', 'cnsds.de', 'codeandscotch.com', 'codivide.com', 'coieo.com', 'coldemail.info', 'compareshippingrates.org', 'completegolfswing.com', 'comwest.de', 'consumerriot.com', 'cool.fr.nf', 'coolandwacky.us', 'coolimpool.org', 'correo.blogos.net', 'cosmorph.com', 'courriel.fr.nf', 'courrieltemporaire.com', 'crankhole.com', 'crapmail.org', 'crastination.de', 'crazespaces.pw', 'crazymailing.com', 'crossroadsmail.com', 'cszbl.com', 'cubiclink.com', 'curryworld.de', 'cust.in', 'cuvox.de', 'cx.de-a.org', 'd.cane.pw', 'd.dialogus.com', 'd3p.dk', 'dacoolest.com', 'daemsteam.com', 'daintly.com', 'dammexe.net', 'dandikmail.com', 'darkharvestfilms.com', 'daryxfox.net', 'dash-pads.com', 'dataarca.com', 'datafilehost', 'datarca.com', 'datazo.ca', 'davidkoh.net', 'davidlcreative.com', 'dayrep.com', 'dbunker.com', 'dcemail.com', 'deadaddress.com', 'deadchildren.org', 'deadfake.cf', 'deadfake.ga', 'deadfake.ml', 'deadfake.tk', 'deadspam.com', 'deagot.com', 'dealja.com', 'dealrek.com', 'deekayen.us', 'defomail.com', 'degradedfun.net', 'delayload.com', 'delayload.net', 'delikkt.de', 'der-kombi.de', 'derkombi.de', 'derluxuswagen.de', 'despam.it', 'despammed.com', 'devnullmail.com', 'dharmatel.net', 'diapaulpainting.com', 'digitalmariachis.com', 'digitalsanctuary.com', 'dildosfromspace.com', 'dingbone.com', 'discard.cf', 'discard.email', 'discard.ga', 'discard.gq', 'discard.ml', 'discard.tk', 'discardmail.com', 'discardmail.de', 'dispo.in', 'dispomail.eu', 'disposable-email.ml', 'disposable.cf', 'disposable.ga', 'disposable.ml', 'disposableaddress.com', 'disposableemailaddresses.com', 'disposableemailaddresses.emailmiser.com', 'disposableinbox.com', 'dispose.it', 'disposeamail.com', 'disposemail.com', 'dispostable.com', 'divermail.com', 'divismail.ru', 'dlemail.ru', 'dm.w3internet.co.uk', 'dm.w3internet.co.ukexample.com', 'dodgeit.com', 'dodgemail.de', 'dodgit.com', 'dodgit.org', 'dodsi.com', 'doiea.com', 'dolphinnet.net', 'domforfb1.tk', 'domforfb18.tk', 'domforfb19.tk', 'domforfb2.tk', 'domforfb23.tk', 'domforfb27.tk', 'domforfb29.tk', 'domforfb3.tk', 'domforfb4.tk', 'domforfb5.tk', 'domforfb6.tk', 'domforfb7.tk', 'domforfb8.tk', 'domforfb9.tk', 'domozmail.com', 'donemail.ru', 'dontreg.com', 'dontsendmespam.de', 'doquier.tk', 'dotman.de', 'dotmsg.com', 'dotslashrage.com', 'douchelounge.com', 'dozvon-spb.ru', 'dr.vankin.de', 'drdrb.com', 'drdrb.net', 'drivetagdev.com', 'droolingfanboy.de', 'dropcake.de', 'droplar.com', 'dropmail.me', 'dspwebservices.com', 'duam.net', 'dudmail.com', 'dukedish.com', 'dump-email.info', 'dumpandjunk.com', 'dumpmail.de', 'dumpyemail.com', 'durandinterstellar.com', 'duskmail.com', 'dw.now.im', 'dx.abuser.eu', 'dx.allowed.org', 'dx.awiki.org', 'dx.ez.lv', 'dx.sly.io', 'dx.soon.it', 'dx.z86.ru', 'dyceroprojects.com', 'dz17.net', 'e-mail.com', 'e-mail.org', 'e.brasx.org', 'e.coza.ro', 'e.ezfill.com', 'e.hecat.es', 'e.hpc.tw', 'e.incq.com', 'e.lee.mx', 'e.ohi.tw', 'e.runi.ca', 'e.sino.tw', 'e.spr.io', 'e.ubm.md', 'e3z.de', 'e4ward.com', 'easy-trash-mail.com', 'easytrashmail.com', 'ebeschlussbuch.de', 'ebs.com.ar', 'ecallheandi.com', 'edinburgh-airporthotels.com', 'edv.to', 'ee1.pl', 'ee2.pl', 'eelmail.com', 'einmalmail.de', 'einrot.com', 'einrot.de', 'eintagsmail.de', 'elearningjournal.org', 'electro.mn', 'elitevipatlantamodels.com', 'email-fake.cf', 'email-fake.ga', 'email-fake.gq', 'email-fake.ml', 'email-fake.tk', 'email-jetable.fr', 'email.cbes.net', 'email.net', 'email60.com', 'emailage.cf', 'emailage.ga', 'emailage.gq', 'emailage.ml', 'emailage.tk', 'emaildienst.de', 'emailgo.de', 'emailias.com', 'emailigo.de', 'emailinfive.com', 'emailisvalid.com', 'emaillime.com', 'emailmiser.com', 'emailproxsy.com', 'emailresort.com', 'emails.ga', 'emailsensei.com', 'emailsingularity.net', 'emailspam.cf', 'emailspam.ga', 'emailspam.gq', 'emailspam.ml', 'emailspam.tk', 'emailtemporanea.com', 'emailtemporanea.net', 'emailtemporar.ro', 'emailtemporario.com.br', 'emailthe.net', 'emailtmp.com', 'emailto.de', 'emailwarden.com', 'emailx.at.hm', 'emailxfer.com', 'emailz.cf', 'emailz.ga', 'emailz.gq', 'emailz.ml', 'emeil.in', 'emeil.ir', 'emil.com', 'emkei.cf', 'emkei.ga', 'emkei.gq', 'emkei.ml', 'emkei.tk', 'eml.pp.ua', 'emz.net', 'enterto.com', 'ephemail.net', 'ephemeral.email', 'er.fir.hk', 'er.moot.es', 'ericjohnson.ml', 'ero-tube.org', 'esc.la', 'escapehatchapp.com', 'esemay.com', 'esgeneri.com', 'esprity.com', 'est.une.victime.ninja', 'etranquil.com', 'etranquil.net', 'etranquil.org', 'evanfox.info', 'evopo.com', 'example.com', 'exitstageleft.net', 'explodemail.com', 'express.net.ua', 'extremail.ru', 'eyepaste.com', 'ezstest.com', 'f.fuirio.com', 'f.fxnxs.com', 'f.hmh.ro', 'f4k.es', 'facebook-email.cf', 'facebook-email.ga', 'facebook-email.ml', 'facebookmail.gq', 'facebookmail.ml', 'fadingemail.com', 'fag.wf', 'failbone.com', 'faithkills.com', 'fake-email.pp.ua', 'fake-mail.cf', 'fake-mail.ga', 'fake-mail.ml', 'fakedemail.com', 'fakeinbox.cf', 'fakeinbox.com', 'fakeinbox.ga', 'fakeinbox.ml', 'fakeinbox.tk', 'fakeinformation.com', 'fakemail.fr', 'fakemailgenerator.com', 'fakemailz.com', 'fammix.com', 'fangoh.com', 'fansworldwide.de', 'fantasymail.de', 'farrse.co.uk', 'fastacura.com', 'fastchevy.com', 'fastchrysler.com', 'fasternet.biz', 'fastkawasaki.com', 'fastmazda.com', 'fastmitsubishi.com', 'fastnissan.com', 'fastsubaru.com', 'fastsuzuki.com', 'fasttoyota.com', 'fastyamaha.com', 'fatflap.com', 'fdfdsfds.com', 'fer-gabon.org', 'fettometern.com', 'fictionsite.com', 'fightallspam.com', 'figjs.com', 'figshot.com', 'fiifke.de', 'filbert4u.com', 'filberts4u.com', 'film-blog.biz', 'filzmail.com', 'fivemail.de', 'fixmail.tk', 'fizmail.com', 'fleckens.hu', 'flemail.ru', 'flowu.com', 'flurred.com', 'fly-ts.de', 'flyinggeek.net', 'flyspam.com', 'foobarbot.net', 'footard.com', 'forecastertests.com', 'forgetmail.com', 'fornow.eu', 'forspam.net', 'foxja.com', 'foxtrotter.info', 'fr.ipsur.org', 'fr33mail.info', 'frapmail.com', 'free-email.cf', 'free-email.ga', 'freebabysittercam.com', 'freeblackbootytube.com', 'freecat.net', 'freedompop.us', 'freefattymovies.com', 'freeletter.me', 'freemail.hu', 'freemail.ms', 'freemails.cf', 'freemails.ga', 'freemails.ml', 'freeplumpervideos.com', 'freeschoolgirlvids.com', 'freesistercam.com', 'freeteenbums.com', 'freundin.ru', 'friendlymail.co.uk', 'front14.org', 'fuckedupload.com', 'fuckingduh.com', 'fudgerub.com', 'funnycodesnippets.com', 'furzauflunge.de', 'fux0ringduh.com', 'fw.moza.pl', 'fyii.de', 'g.airsi.de', 'g.asu.su', 'g.garizo.com', 'g.hmail.us', 'g.rbb.org', 'g.tefl.ro', 'g.tiv.cc', 'g.vda.ro', 'g4hdrop.us', 'galaxy.tv', 'gamegregious.com', 'garbagecollector.org', 'garbagemail.org', 'gardenscape.ca', 'garliclife.com', 'garrifulio.mailexpire.com', 'garrymccooey.com', 'gav0.com', 'gawab.com', 'gehensiemirnichtaufdensack.de', 'geldwaschmaschine.de', 'gelitik.in', 'genderfuck.net', 'geschent.biz', 'get-mail.cf', 'get-mail.ga', 'get-mail.ml', 'get-mail.tk', 'get.pp.ua', 'get1mail.com', 'get2mail.fr', 'getairmail.cf', 'getairmail.com', 'getairmail.ga', 'getairmail.gq', 'getairmail.ml', 'getairmail.tk', 'getmails.eu', 'getonemail.com', 'getonemail.net', 'gg.nh3.ro', 'ghosttexter.de', 'giaiphapmuasam.com', 'giantmail.de', 'ginzi.be', 'ginzi.co.uk', 'ginzi.es', 'ginzi.net', 'ginzy.co.uk', 'ginzy.eu', 'girlsindetention.com', 'girlsundertheinfluence.com', 'gishpuppy.com', 'glitch.sx', 'globaltouron.com', 'glucosegrin.com', 'gmal.com', 'gmial.com', 'gmx.us', 'gnctr-calgary.com', 'go.arduino.hk', 'go.cdpa.cc', 'go.irc.so', 'go.jmail.ro', 'go.jwork.ru', 'goemailgo.com', 'gomail.in', 'gorillaswithdirtyarmpits.com', 'gothere.biz', 'gotmail.com', 'gotmail.net', 'gotmail.org', 'gotti.otherinbox.com', 'gowikibooks.com', 'gowikicampus.com', 'gowikicars.com', 'gowikifilms.com', 'gowikigames.com', 'gowikimusic.com', 'gowikinetwork.com', 'gowikitravel.com', 'gowikitv.com', 'grandmamail.com', 'grandmasmail.com', 'great-host.in', 'greensloth.com', 'greggamel.com', 'greggamel.net', 'gregorsky.zone', 'gregorygamel.com', 'gregorygamel.net', 'grr.la', 'gs-arc.org', 'gsredcross.org', 'gsrv.co.uk', 'gudanglowongan.com', 'guerillamail.biz', 'guerillamail.com', 'guerillamail.de', 'guerillamail.info', 'guerillamail.net', 'guerillamail.org', 'guerillamailblock.com', 'guerrillamail.biz', 'guerrillamail.com', 'guerrillamail.de', 'guerrillamail.info', 'guerrillamail.net', 'guerrillamail.org', 'guerrillamailblock.com', 'gustr.com', 'gynzi.co.uk', 'gynzi.es', 'gynzy.at', 'gynzy.es', 'gynzy.eu', 'gynzy.gr', 'gynzy.info', 'gynzy.lt', 'gynzy.mobi', 'gynzy.pl', 'gynzy.ro', 'gynzy.sk', 'h.mintemail.com', 'h8s.org', 'habitue.net', 'hacccc.com', 'hackthatbit.ch', 'hahawrong.com', 'haltospam.com', 'harakirimail.com', 'hartbot.de', 'hat-geld.de', 'hatespam.org', 'hawrong.com', 'hazelnut4u.com', 'hazelnuts4u.com', 'hazmatshipping.org', 'heathenhammer.com', 'heathenhero.com', 'hellodream.mobi', 'helloricky.com', 'helpinghandtaxcenter.org', 'herp.in', 'herpderp.nl', 'hiddentragedy.com', 'hidemail.de', 'hidzz.com', 'highbros.org', 'hmamail.com', 'hoanggiaanh.com', 'hochsitze.com', 'hopemail.biz', 'hot-mail.cf', 'hot-mail.ga', 'hot-mail.gq', 'hot-mail.ml', 'hot-mail.tk', 'hotmai.com', 'hotmial.com', 'hotpop.com', 'hq.okzk.com', 'hulapla.de', 'humaility.com', 'humn.ws.gy', 'hungpackage.com', 'hush.ai', 'hush.com', 'hushmail.com', 'hushmail.me', 'huskion.net', 'hvastudiesucces.nl', 'hwsye.net', 'ibnuh.bz', 'icantbelieveineedtoexplainthisshit.com', 'icx.in', 'ieatspam.eu', 'ieatspam.info', 'ieh-mail.de', 'ignoremail.com', 'ihateyoualot.info', 'iheartspam.org', 'ikbenspamvrij.nl', 'illistnoise.com', 'ilovespam.com', 'imails.info', 'imgof.com', 'imgv.de', 'imstations.com', 'inbax.tk', 'inbound.plus', 'inbox.si', 'inbox2.info', 'inboxalias.com', 'inboxclean.com', 'inboxclean.org', 'inboxdesign.me', 'inboxed.im', 'inboxed.pw', 'inboxproxy.com', 'inboxstore.me', 'inclusiveprogress.com', 'incognitomail.com', 'incognitomail.net', 'incognitomail.org', 'indieclad.com', 'indirect.ws', 'ineec.net', 'infocom.zp.ua', 'inoutmail.de', 'inoutmail.eu', 'inoutmail.info', 'inoutmail.net', 'insanumingeniumhomebrew.com', 'insorg-mail.info', 'instant-mail.de', 'instantemailaddress.com', 'internetoftags.com', 'interstats.org', 'intersteller.com', 'iozak.com', 'ip.nm7.cc', 'ip4.pp.ua', 'ip6.li', 'ip6.pp.ua', 'ipoo.org', 'irish2me.com', 'iroid.com', 'ironiebehindert.de', 'irssi.tv', 'is.af', 'isukrainestillacountry.com', 'it7.ovh', 'itunesgiftcodegenerator.com', 'iwi.net', 'j-p.us', 'j.svxr.org', 'jafps.com', 'jdmadventures.com', 'jellyrolls.com', 'jetable.com', 'jetable.fr.nf', 'jetable.net', 'jetable.org', 'jetable.pp.ua', 'jnxjn.com', 'jobbikszimpatizans.hu', 'jobposts.net', 'jobs-to-be-done.net', 'joelpet.com', 'joetestalot.com', 'jopho.com', 'jourrapide.com', 'jp.ftp.sh', 'jsrsolutions.com', 'jungkamushukum.com', 'junk.to', 'junk1e.com', 'junkmail.ga', 'junkmail.gq', 'k.aelo.es', 'k.avls.pt', 'k.bgx.ro', 'k.cylab.org', 'k.kaovo.com', 'k.kon42.com', 'k.vesa.pw', 'kakadua.net', 'kalapi.org', 'kamsg.com', 'kariplan.com', 'kartvelo.com', 'kasmail.com', 'kaspop.com', 'kcrw.de', 'keepmymail.com', 'keinhirn.de', 'keipino.de', 'kemptvillebaseball.com', 'kennedy808.com', 'killmail.com', 'killmail.net', 'kimsdisk.com', 'kingsq.ga', 'kiois.com', 'kir.ch.tc', 'kismail.ru', 'kisstwink.com', 'kitnastar.com', 'klassmaster.com', 'klassmaster.net', 'kloap.com', 'kludgemush.com', 'klzlk.com', 'kmhow.com', 'kommunity.biz', 'kook.ml', 'kopagas.com', 'kopaka.net', 'kosmetik-obatkuat.com', 'kostenlosemailadresse.de', 'koszmail.pl', 'krypton.tk', 'kuhrap.com', 'kulturbetrieb.info', 'kurzepost.de', 'kwift.net', 'kwilco.net', 'l-c-a.us', 'l.logular.com', 'l33r.eu', 'labetteraverouge.at', 'lackmail.net', 'lags.us', 'lakelivingstonrealestate.com', 'landmail.co', 'laoeq.com', 'lastmail.co', 'lastmail.com', 'lavabit.com', 'lawlita.com', 'lazyinbox.com', 'leeching.net', 'lellno.gq', 'letmeinonthis.com', 'letthemeatspam.com', 'lez.se', 'lhsdv.com', 'liamcyrus.com', 'lifebyfood.com', 'lifetotech.com', 'ligsb.com', 'lilo.me', 'lindenbaumjapan.com', 'link2mail.net', 'linuxmail.so', 'litedrop.com', 'lkgn.se', 'llogin.ru', 'loadby.us', 'locomodev.net', 'login-email.cf', 'login-email.ga', 'login-email.ml', 'login-email.tk', 'loh.pp.ua', 'loin.in', 'lol.meepsheep.eu', 'lol.ovpn.to', 'lolfreak.net', 'lolmail.biz', 'lookugly.com', 'lopl.co.cc', 'lortemail.dk', 'losemymail.com', 'lovemeleaveme.com', 'lpfmgmtltd.com', 'lr7.us', 'lr78.com', 'lroid.com', 'lru.me', 'luckymail.org', 'lukecarriere.com', 'lukemail.info', 'lukop.dk', 'luv2.us', 'lyfestylecreditsolutions.com', 'm.ddcrew.com', 'm21.cc', 'm4ilweb.info', 'ma1l.bij.pl', 'maboard.com', 'mac.hush.com', 'macromaid.com', 'magamail.com', 'magicbox.ro', 'maidlow.info', 'mail-filter.com', 'mail-owl.com', 'mail-temporaire.com', 'mail-temporaire.fr', 'mail.bccto.me', 'mail.by', 'mail.mezimages.net', 'mail.zp.ua', 'mail114.net', 'mail1a.de', 'mail21.cc', 'mail2rss.org', 'mail2world.com', 'mail333.com', 'mail4trash.com', 'mail666.ru', 'mail707.com', 'mail72.com', 'mailback.com', 'mailbidon.com', 'mailbiz.biz', 'mailblocks.com', 'mailbucket.org', 'mailcat.biz', 'mailcatch.com', 'mailchop.com', 'mailde.de', 'mailde.info', 'maildrop.cc', 'maildrop.cf', 'maildrop.ga', 'maildrop.gq', 'maildrop.ml', 'maildu.de', 'maildx.com', 'maileater.com', 'mailed.in', 'mailed.ro', 'maileimer.de', 'mailexpire.com', 'mailfa.tk', 'mailforspam.com', 'mailfree.ga', 'mailfree.gq', 'mailfree.ml', 'mailfreeonline.com', 'mailfs.com', 'mailguard.me', 'mailhazard.com', 'mailhazard.us', 'mailhz.me', 'mailimate.com', 'mailin8r.com', 'mailinatar.com', 'mailinater.com', 'mailinator.co.uk', 'mailinator.com', 'mailinator.gq', 'mailinator.info', 'mailinator.net', 'mailinator.org', 'mailinator.us', 'mailinator2.com', 'mailincubator.com', 'mailismagic.com', 'mailita.tk', 'mailjunk.cf', 'mailjunk.ga', 'mailjunk.gq', 'mailjunk.ml', 'mailjunk.tk', 'mailmate.com', 'mailme.gq', 'mailme.ir', 'mailme.lv', 'mailme24.com', 'mailmetrash.com', 'mailmoat.com', 'mailms.com', 'mailnator.com', 'mailnesia.com', 'mailnull.com', 'mailonaut.com', 'mailorc.com', 'mailorg.org', 'mailpick.biz', 'mailproxsy.com', 'mailquack.com', 'mailrock.biz', 'mailsac.com', 'mailscrap.com', 'mailseal.de', 'mailshell.com', 'mailsiphon.com', 'mailslapping.com', 'mailslite.com', 'mailtemp.info', 'mailtemporaire.com', 'mailtemporaire.fr', 'mailtome.de', 'mailtothis.com', 'mailtrash.net', 'mailtv.net', 'mailtv.tv', 'mailzi.ru', 'mailzilla.com', 'mailzilla.org', 'mailzilla.orgmbx.cc', 'makemetheking.com', 'malahov.de', 'malayalamdtp.com', 'manifestgenerator.com', 'mansiondev.com', 'manybrain.com', 'markmurfin.com', 'mbx.cc', 'mcache.net', 'mciek.com', 'mega.zik.dj', 'meinspamschutz.de', 'meltmail.com', 'messagebeamer.de', 'messwiththebestdielikethe.rest', 'mezimages.net', 'mfsa.ru', 'miaferrari.com', 'midcoastcustoms.com', 'midcoastcustoms.net', 'midcoastsolutions.com', 'midcoastsolutions.net', 'midlertidig.com', 'midlertidig.net', 'midlertidig.org', 'mierdamail.com', 'migmail.net', 'migmail.pl', 'migumail.com', 'mijnhva.nl', 'mildin.org.ua', 'ministry-of-silly-walks.de', 'mintemail.com', 'misterpinball.de', 'mjukglass.nu', 'mkpfilm.com', 'ml8.ca', 'moakt.com', 'mobi.web.id', 'mobileninja.co.uk', 'moburl.com', 'mockmyid.com', 'mohmal.com', 'momentics.ru', 'moncourrier.fr.nf', 'monemail.fr.nf', 'moneypipe.net', 'monmail.fr.nf', 'monumentmail.com', 'moonwake.com', 'mor19.uu.gl', 'moreawesomethanyou.com', 'moreorcs.com', 'motique.de', 'mountainregionallibrary.net', 'mox.pp.ua', 'ms9.mailslite.com', 'msa.minsmail.com', 'msb.minsmail.com', 'msgos.com', 'mspeciosa.com', 'mswork.ru', 'msxd.com', 'mt2009.com', 'mt2014.com', 'mt2015.com', 'mtmdev.com', 'muathegame.com', 'muchomail.com', 'mucincanon.com', 'mutant.me', 'mwarner.org', 'mx0.wwwnew.eu', 'mxfuel.com', 'my.efxs.ca', 'my10minutemail.com', 'mybitti.de', 'mycard.net.ua', 'mycleaninbox.net', 'mycorneroftheinter.net', 'mydemo.equipment', 'myecho.es', 'myemailboxy.com', 'mykickassideas.com', 'mymail-in.net', 'mymailoasis.com', 'mynetstore.de', 'myopang.com', 'mypacks.net', 'mypartyclip.de', 'myphantomemail.com', 'mysamp.de', 'myspaceinc.com', 'myspaceinc.net', 'myspaceinc.org', 'myspacepimpedup.com', 'myspamless.com', 'mytemp.email', 'mytempemail.com', 'mytempmail.com', 'mytrashmail.com', 'mywarnernet.net', 'myzx.com', 'n.rabin.ca', 'n1nja.org', 'nabuma.com', 'nakedtruth.biz', 'nanonym.ch', 'nationalgardeningclub.com', 'naver.com', 'negated.com', 'neomailbox.com', 'nepwk.com', 'nervmich.net', 'nervtmich.net', 'netmails.com', 'netmails.net', 'netricity.nl', 'netris.net', 'netviewer-france.com', 'netzidiot.de', 'nevermail.de', 'new.apps.dj', 'nextstopvalhalla.com', 'nfast.net', 'nguyenusedcars.com', 'nice-4u.com', 'nicknassar.com', 'nincsmail.hu', 'niwl.net', 'nmail.cf', 'nnh.com', 'nnot.net', 'no-spam.ws', 'no-ux.com', 'noblepioneer.com', 'nobugmail.com', 'nobulk.com', 'nobuma.com', 'noclickemail.com', 'nodezine.com', 'nogmailspam.info', 'nokiamail.com', 'nomail.pw', 'nomail.xl.cx', 'nomail2me.com', 'nomorespamemails.com', 'nonspam.eu', 'nonspammer.de', 'noref.in', 'norseforce.com', 'nospam.wins.com.br', 'nospam.ze.tc', 'nospam4.us', 'nospamfor.us', 'nospamthanks.info', 'nothingtoseehere.ca', 'notmailinator.com', 'notrnailinator.com', 'notsharingmy.info', 'nowhere.org', 'nowmymail.com', 'ntlhelp.net', 'nubescontrol.com', 'nullbox.info', 'nurfuerspam.de', 'nus.edu.sg', 'nuts2trade.com', 'nwldx.com', 'ny7.me', 'o.cavi.mx', 'o.civx.org', 'o.cnew.ir', 'o.jpco.org', 'o.mm5.se', 'o.opp24.com', 'o.rma.ec', 'o.sin.cl', 'o.yedi.org', 'o2stk.org', 'o7i.net', 'obfusko.com', 'objectmail.com', 'obobbo.com', 'obxpestcontrol.com', 'odaymail.com', 'odnorazovoe.ru', 'oerpub.org', 'offshore-proxies.net', 'ohaaa.de', 'okclprojects.com', 'okrent.us', 'olypmall.ru', 'omail.pro', 'omnievents.org', 'one-time.email', 'oneoffemail.com', 'oneoffmail.com', 'onewaymail.com', 'onlatedotcom.info', 'online.ms', 'onlineidea.info', 'onqin.com', 'ontyne.biz', 'oolus.com', 'oopi.org', 'opayq.com', 'ordinaryamerican.net', 'oshietechan.link', 'otherinbox.com', 'ourklips.com', 'ourpreviewdomain.com', 'outlawspam.com', 'ovpn.to', 'owlpic.com', 'ownsyou.de', 'oxopoha.com', 'p.mm.my', 'pa9e.com', 'pagamenti.tk', 'pancakemail.com', 'paplease.com', 'pastebitch.com', 'pcusers.otherinbox.com', 'penisgoes.in', 'pepbot.com', 'peterdethier.com', 'petrzilka.net', 'pfui.ru', 'photomark.net', 'phpbb.uu.gl', 'pi.vu', 'pimpedupmyspace.com', 'pinehill-seattle.org', 'pingir.com', 'pisls.com', 'pjjkp.com', 'plexolan.de', 'plhk.ru', 'plw.me', 'po.bot.nu', 'poczta.onet.pl', 'poh.pp.ua', 'pojok.ml', 'pokiemobile.com', 'politikerclub.de', 'pooae.com', 'poofy.org', 'pookmail.com', 'poopiebutt.club', 'popesodomy.com', 'popgx.com', 'postacin.com', 'postonline.me', 'poutineyourface.com', 'powered.name', 'powlearn.com', 'pp.ua', 'primabananen.net', 'privacy.net', 'privatdemail.net', 'privy-mail.com', 'privy-mail.de', 'privymail.de', 'pro-tag.org', 'procrackers.com', 'projectcl.com', 'propscore.com', 'proxymail.eu', 'proxyparking.com', 'prtnx.com', 'prtz.eu', 'pub.ftpinc.ca', 'punkass.com', 'puk.us.to', 'purcell.email', 'purelogistics.org', 'put2.net', 'putthisinyourspamdatabase.com', 'pwrby.com', 'px.dhm.ro', 'q.awatum.de', 'q.tic.ec', 'qasti.com', 'qipmail.net', 'qisdo.com', 'qisoa.com', 'qoika.com', 'qs.dp76.com', 'qs.grish.de', 'quadrafit.com', 'quickinbox.com', 'quickmail.nl', 'qvy.me', 'qwickmail.com', 'r.ctos.ch', 'r4nd0m.de', 'radiku.ye.vc', 'raetp9.com', 'raketenmann.de', 'rancidhome.net', 'randomail.net', 'raqid.com', 'rax.la', 'raxtest.com', 'rcpt.at', 'rcs.gaggle.net', 'reallymymail.com', 'realtyalerts.ca', 'receiveee.chickenkiller.com', 'receiveee.com', 'recipeforfailure.com', 'recode.me', 'reconmail.com', 'recyclemail.dk', 'redfeathercrow.com', 'regbypass.com', 'regbypass.comsafe-mail.net', 'rejectmail.com', 'reliable-mail.com', 'remail.cf', 'remail.ga', 'remarkable.rocks', 'remote.li', 'reptilegenetics.com', 'revolvingdoorhoax.org', 'rhyta.com', 'riddermark.de', 'risingsuntouch.com', 'rk9.chickenkiller.com', 'rklips.com', 'rmqkr.net', 'rnailinator.com', 'robertspcrepair.com', 'ronnierage.net', 'rotaniliam.com', 'rowe-solutions.com', 'royal.net', 'royaldoodles.org', 'rppkn.com', 'rr.ige.es', 'rtrtr.com', 'ruffrey.com', 'rumgel.com', 'rustydoor.com', 'rx.dred.ru', 'rx.qc.to', 's.sast.ro', 's.scay.net', 's0ny.net', 's33db0x.com', 'sabrestlouis.com', 'sackboii.com', 'safe-mail.net', 'safersignup.de', 'safetymail.info', 'safetypost.de', 'saharanightstempe.com', 'samsclass.info', 'sandelf.de', 'sandwhichvideo.com', 'sanfinder.com', 'sanim.net', 'sanstr.com', 'satukosong.com', 'sausen.com', 'saynotospams.com', 'scatmail.com', 'schachrol.com', 'schafmail.de', 'schmeissweg.tk', 'schrott-email.de', 'sd3.in', 'secmail.pw', 'secretemail.de', 'secure-mail.biz', 'secure-mail.cc', 'secured-link.net', 'securehost.com.es', 'seekapps.com', 'sejaa.lv', 'selfdestructingmail.com', 'selfdestructingmail.org', 'sendfree.org', 'sendingspecialflyers.com', 'sendspamhere.com', 'senseless-entertainment.com', 'server.ms', 'services391.com', 'sexforswingers.com', 'sexical.com', 'sharedmailbox.org', 'sharklasers.com', 'shhmail.com', 'shhuut.org', 'shieldedmail.com', 'shieldemail.com', 'shiftmail.com', 'shipfromto.com', 'shiphazmat.org', 'shipping-regulations.com', 'shippingterms.org', 'shitmail.de', 'shitmail.me', 'shitmail.org', 'shitware.nl', 'shmeriously.com', 'shortmail.net', 'shotmail.ru', 'showslow.de', 'shrib.com', 'shut.name', 'shut.ws', 'sibmail.com', 'sify.com', 'simpleitsecurity.info', 'sinfiltro.cl', 'singlespride.com', 'sinnlos-mail.de', 'siteposter.net', 'sizzlemctwizzle.com', 'skeefmail.com', 'skkk.edu.my', 'sky-inbox.com', 'sky-ts.de', 'slapsfromlastnight.com', 'slaskpost.se', 'slave-auctions.net', 'slopsbox.com', 'slothmail.net', 'slushmail.com', 'smapfree24.com', 'smapfree24.de', 'smapfree24.eu', 'smapfree24.info', 'smapfree24.org', 'smashmail.de', 'smellfear.com', 'smellrear.com', 'smtp99.com', 'smwg.info', 'snakemail.com', 'sneakemail.com', 'sneakmail.de', 'snkmail.com', 'socialfurry.org', 'sofimail.com', 'sofort-mail.de', 'sofortmail.de', 'softpls.asia', 'sogetthis.com', 'sohu.com', 'soisz.com', 'solvemail.info', 'solventtrap.wiki', 'soodmail.com', 'soodomail.com', 'soodonims.com', 'spam-be-gone.com', 'spam.la', 'spam.org.es', 'spam.su', 'spam4.me', 'spamail.de', 'spamarrest.com', 'spamavert.com', 'spambob.com', 'spambob.net', 'spambob.org', 'spambog.com', 'spambog.de', 'spambog.net', 'spambog.ru', 'spambooger.com', 'spambox.info', 'spambox.irishspringrealty.com', 'spambox.org', 'spambox.us', 'spamcero.com', 'spamcon.org', 'spamcorptastic.com', 'spamcowboy.com', 'spamcowboy.net', 'spamcowboy.org', 'spamday.com', 'spamdecoy.net', 'spamex.com', 'spamfighter.cf', 'spamfighter.ga', 'spamfighter.gq', 'spamfighter.ml', 'spamfighter.tk', 'spamfree.eu', 'spamfree24.com', 'spamfree24.de', 'spamfree24.eu', 'spamfree24.info', 'spamfree24.net', 'spamfree24.org', 'spamgoes.in', 'spamherelots.com', 'spamhereplease.com', 'spamhole.com', 'spamify.com', 'spaminator.de', 'spamkill.info', 'spaml.com', 'spaml.de', 'spamlot.net', 'spammotel.com', 'spamobox.com', 'spamoff.de', 'spamsalad.in', 'spamslicer.com', 'spamspot.com', 'spamstack.net', 'spamthis.co.uk', 'spamthisplease.com', 'spamtrail.com', 'spamtroll.net', 'speed.1s.fr', 'speedgaus.net', 'spikio.com', 'spoofmail.de', 'spritzzone.de', 'spybox.de', 'squizzy.de', 'sr.ro.lt', 'sry.li', 'ss.hi5.si', 'ss.icx.ro', 'ss.undo.it', 'ssoia.com', 'stanfordujjain.com', 'starlight-breaker.net', 'startfu.com', 'startkeys.com', 'statdvr.com', 'stathost.net', 'statiix.com', 'steambot.net', 'stinkefinger.net', 'stop-my-spam.cf', 'stop-my-spam.com', 'stop-my-spam.ga', 'stop-my-spam.ml', 'stop-my-spam.pp.ua', 'stop-my-spam.tk', 'streetwisemail.com', 'stuffmail.de', 'stumpfwerk.com', 'sub.internetoftags.com', 'suburbanthug.com', 'suckmyd.com', 'sudolife.me', 'sudolife.net', 'sudomail.biz', 'sudomail.com', 'sudomail.net', 'sudoverse.com', 'sudoverse.net', 'sudoweb.net', 'sudoworld.com', 'sudoworld.net', 'suioe.com', 'super-auswahl.de', 'supergreatmail.com', 'supermailer.jp', 'superplatyna.com', 'superrito.com', 'superstachel.de', 'suremail.info', 'svk.jp', 'sweetxxx.de', 'swift10minutemail.com', 'sylvannet.com', 't.psh.me', 'tafmail.com', 'tafoi.gr', 'tagmymedia.com', 'tagyourself.com', 'talkinator.com', 'tanukis.org', 'tapchicuoihoi.com', 'tb-on-line.net', 'te.adiq.eu', 'techemail.com', 'techgroup.me', 'teewars.org', 'telecomix.pl', 'teleworm.com', 'teleworm.us', 'temp-mail.com', 'temp-mail.de', 'temp-mail.org', 'temp-mail.ru', 'temp.bartdevos.be', 'temp.emeraldwebmail.com', 'temp.headstrong.de', 'tempail.com', 'tempalias.com', 'tempe-mail.com', 'tempemail.biz', 'tempemail.co.za', 'tempemail.com', 'tempemail.net', 'tempinbox.co.uk', 'tempinbox.com', 'tempmail.co', 'tempmail.eu', 'tempmail.it', 'tempmail2.com', 'tempmaildemo.com', 'tempmailer.com', 'tempmailer.de', 'tempomail.fr', 'temporarily.de', 'temporarioemail.com.br', 'temporaryemail.net', 'temporaryemail.us', 'temporaryforwarding.com', 'temporaryinbox.com', 'temporarymailaddress.com', 'tempsky.com', 'tempthe.net', 'tempymail.com', 'testudine.com', 'th.edgex.ru', 'thanksnospam.info', 'thankyou2010.com', 'thc.st', 'theaviors.com', 'thebearshark.com', 'thecloudindex.com', 'thediamants.org', 'thelimestones.com', 'thembones.com.au', 'themostemail.com', 'thereddoors.online', 'thescrappermovie.com', 'theteastory.info', 'thietbivanphong.asia', 'thisisnotmyrealemail.com', 'thismail.net', 'thisurl.website', 'thnikka.com', 'thraml.com', 'thrma.com', 'throam.com', 'thrott.com', 'throwawayemailaddress.com', 'throwawaymail.com', 'thunkinator.org', 'thxmate.com', 'tilien.com', 'timgiarevn.com', 'timkassouf.com', 'tinyurl24.com', 'tittbit.in', 'tizi.com', 'tlpn.org', 'tm.tosunkaya.com', 'tmail.com', 'tmail.ws', 'tmailinator.com', 'tmpjr.me', 'toddsbighug.com', 'toiea.com', 'tokem.co', 'tokenmail.de', 'tonymanso.com', 'toomail.biz', 'top101.de', 'top1mail.ru', 'top1post.ru', 'topofertasdehoy.com', 'topranklist.de', 'toprumours.com', 'tormail.org', 'toss.pw', 'totalvista.com', 'totesmail.com', 'tp-qa-mail.com', 'tradermail.info', 'tranceversal.com', 'trash-amil.com', 'trash-mail.at', 'trash-mail.cf', 'trash-mail.com', 'trash-mail.de', 'trash-mail.ga', 'trash-mail.gq', 'trash-mail.ml', 'trash-mail.tk', 'trash2009.com', 'trash2010.com', 'trash2011.com', 'trashcanmail.com', 'trashdevil.com', 'trashdevil.de', 'trashemail.de', 'trashinbox.com', 'trashmail.at', 'trashmail.com', 'trashmail.de', 'trashmail.me', 'trashmail.net', 'trashmail.org', 'trashmail.ws', 'trashmailer.com', 'trashymail.com', 'trashymail.net', 'trasz.com', 'trayna.com', 'trbvm.com', 'trbvn.com', 'trbvo.com', 'trialmail.de', 'trickmail.net', 'trillianpro.com', 'trollproject.com', 'tropicalbass.info', 'trungtamtoeic.com', 'tryalert.com', 'ttszuo.xyz', 'tualias.com', 'turoid.com', 'turual.com', 'twinmail.de', 'twoweirdtricks.com', 'txtadvertise.com', 'ty.ceed.se', 'tyldd.com', 'u.42o.org', 'u.duk33.com', 'u.hs.vc', 'u.jdz.ro', 'u.mji.ro', 'u.qibl.at', 'u.oroki.de', 'u.ozyl.de', 'u.rvb.ro', 'u.thex.ro', 'u.tkitc.de', 'u.wef.gr', 'ubismail.net', 'ufacturing.com', 'uggsrock.com', 'uguuchantele.com', 'uhhu.ru', 'umail.net', 'unimark.org', 'unit7lahaina.com', 'unmail.ru', 'upliftnow.com', 'uplipht.com', 'uploadnolimit.com', 'urfunktion.se', 'uroid.com', 'us.af', 'username.e4ward.com', 'utiket.us', 'uwork4.us', 'ux.dob.jp', 'ux.uk.to', 'uyhip.com', 'vaati.org', 'valemail.net', 'valhalladev.com', 'venompen.com', 'verdejo.com', 'veryday.ch', 'veryday.eu', 'veryday.info', 'veryrealemail.com', 'vfemail.net', 'vg.dab.ro', 'victoriantwins.com', 'vidchart.com', 'viditag.com', 'viewcastmedia.com', 'viewcastmedia.net', 'viewcastmedia.org', 'vikingsonly.com', 'vinernet.com', 'vipmail.name', 'vipmail.pw', 'vipxm.net', 'viralplays.com', 'vixletdev.com', 'vkcode.ru', 'vmailing.info', 'vmani.com', 'vmpanda.com', 'vo.yoo.ro', 'voidbay.com', 'vomoto.com', 'vorga.org', 'votiputox.org', 'voxelcore.com', 'vp.ycare.de', 'vpn.st', 'vsimcard.com', 'vubby.com', 'vztc.com', 'wakingupesther.com', 'walala.org', 'walkmail.net', 'walkmail.ru', 'wasteland.rfc822.org', 'watch-harry-potter.com', 'watchever.biz', 'watchfull.net', 'watchironman3onlinefreefullmovie.com', 'wbml.net', 'we.geteit.com', 'we.ldop.com', 'we.ldtp.com', 'we.qq.my', 'we.vrmtr.com', 'we.wallm.com', 'web-mail.pp.ua', 'webemail.me', 'webm4il.info', 'webtrip.ch', 'webuser.in', 'wee.my', 'wefjo.grn.cc', 'weg-werf-email.de', 'wegwerf-email-addressen.de', 'wegwerf-email-adressen.de', 'wegwerf-email.de', 'wegwerf-email.net', 'wegwerf-emails.de', 'wegwerfadresse.de', 'wegwerfemail.com', 'wegwerfemail.de', 'wegwerfemail.net', 'wegwerfemail.org', 'wegwerfemailadresse.com', 'wegwerfmail.de', 'wegwerfmail.info', 'wegwerfmail.net', 'wegwerfmail.org', 'wegwerpmailadres.nl', 'wegwrfmail.de', 'wegwrfmail.net', 'wegwrfmail.org', 'welikecookies.com', 'wetrainbayarea.com', 'wetrainbayarea.org', 'wg0.com', 'wh4f.org', 'whatiaas.com', 'whatifanalytics.com', 'whatpaas.com', 'whatsaas.com', 'whiffles.org', 'whopy.com', 'whtjddn.33mail.com', 'whyspam.me', 'wibblesmith.com', 'wickmail.net', 'widget.gg', 'wilemail.com', 'willhackforfood.biz', 'willselfdestruct.com', 'wimsg.com', 'winemaven.info', 'wmail.cf', 'wolfsmail.tk', 'wollan.info', 'worldspace.link', 'wovz.cu.cc', 'wr.moeri.org', 'wralawfirm.com', 'writeme.us', 'wronghead.com', 'ws.yodx.ro', 'wuzup.net', 'wuzupmail.net', 'www.bccto.me', 'www.e4ward.com', 'www.gishpuppy.com', 'www.mailinator.com', 'wwwnew.eu', 'x.ip6.li', 'x1x.spb.ru', 'x24.com', 'xagloo.co', 'xagloo.com', 'xcompress.com', 'xcpy.com', 'xemaps.com', 'xents.com', 'xing886.uu.gl', 'xjoi.com', 'xmail.com', 'xmaily.com', 'xn--9kq967o.com', 'xoxox.cc', 'xrho.com', 'xwaretech.com', 'xwaretech.info', 'xwaretech.net', 'xww.ro', 'xyzfree.net', 'y.bcb.ro', 'y.epb.ro', 'y.gzb.ro', 'y.tyhe.ro', 'yanet.me', 'yapped.net', 'yaqp.com', 'ye.nonze.ro', 'yep.it', 'yert.ye.vc', 'yhg.biz', 'ynmrealty.com', 'yogamaven.com', 'yomail.info', 'yopmail.com', 'yopmail.fr', 'yopmail.gq', 'yopmail.net', 'yopmail.pp.ua', 'you-spam.com', 'yougotgoated.com', 'youmail.ga', 'youmailr.com', 'youneedmore.info', 'yourdomain.com', 'yourewronghereswhy.com', 'yourlms.biz', 'ypmail.webarnak.fr.eu.org', 'yspend.com', 'yugasandrika.com', 'yui.it', 'yuurok.com', 'yxzx.net', 'z1p.biz', 'za.com', 'ze.gally.jp', 'zebins.com', 'zebins.eu', 'zehnminuten.de', 'zehnminutenmail.de', 'zepp.dk', 'zetmail.com', 'zfymail.com', 'zippymail.info', 'zipsendtest.com', 'zoaxe.com', 'zoemail.com', 'zoemail.net', 'zoemail.org', 'zoetropes.org', 'zombie-hive.com', 'zomg.info', 'zumpul.com', 'zxcv.com', 'zxcvbnm.com', 'zzz.com',
        
        
        /* I Added These */
        '0815.ru0clickemail.com', '0sg.net', '12hourmail.com', '3126.com', '3g.ua', '50e.info', 'abwesend.de', 'addcom.de', 'agnitumhost.net', 'alpenjodel.de', 'alphafrau.de', 'amorki.pl', 'antichef.com', 'antichef.net', 'autosfromus.com', 'baldmama.de', 'baldpapa.de', 'ballyfinance.com', 'betriebsdirektor.de', 'bigmir.net', 'bin-wieder-da.de', 'bk.ru', 'bleib-bei-mir.de', 'blockfilter.com', 'bluebottle.com', 'bonbon.net', 'briefemail.com', 'brokenvalve.com', 'brokenvalve.org', 'buerotiger.de', 'buy-24h.net.ru', 'cashette.com', 'center-mail.de', 'centermail.at', 'centermail.ch', 'centermail.de', 'centermail.info', 'cghost.s-a-d.de', 'chongsoft.org', 'coole-files.de', 'cyber-matrix.com', 'dating4best.net', 'dfgh.net', 'die-besten-bilder.de', 'die-genossen.de', 'die-optimisten.de', 'die-optimisten.net', 'diemailbox.de', 'digital-filestore.de', 'directbox.com', 'discartmail.com', 'docmail.cz', 'dogit.com', 'dontsentmespam.de', 'download-privat.de', 'dumpmail.com', 'dyndns.org', 'e-postkasten.com', 'e-postkasten.de', 'e-postkasten.eu', 'e-postkasten.info', 'email.org', 'email4u.info', 'emailtaxi.de', 'faecesmail.me', 'fahr-zur-hoelle.org', 'falseaddress.com', 'farifluset.mailexpire.com', 'feinripptraeger.de', 'fettabernett.de', 'fishfuse.com', 'freemeilaadressforall.net', 'freudenkinder.de', 'fromru.com', 'gentlemansclub.de', 'gold-profits.info', 'goldtoolbox.com', 'golfilla.info', 'hab-verschlafen.de', 'habmalnefrage.de', 'herr-der-mails.de', 'home.de', 'i.ua', 'ich-bin-verrueckt-nach-dir.de', 'ich-will-net.de', 'inbox.ru', 'inerted.com', 'inet.ua', 'inmail24.com', 'ist-allein.info', 'ist-einmalig.de', 'ist-ganz-allein.de', 'ist-willig.de', 'izmail.net', 'jetable.de', 'jetfix.ee', 'jetzt-bin-ich-dran.com', 'jn-club.de', 'junkmail.com', 'kaffeeschluerfer.com', 'kaffeeschluerfer.de', 'kinglibrary.net', 'kommespaeter.de', 'krim.ws', 'kuh.mu', 'lass-es-geschehen.de', 'liebt-dich.info', 'list.ru', 'listomail.com', 'loveyouforever.de', 'maennerversteherin.com', 'maennerversteherin.de', 'mail.htl22.at', 'mail.misterpinball.de', 'mail.ru', 'mail.svenz.eu', 'mail15.com', 'mail4days.com', 'mail4u.info', 'mailinblack.com', 'mailueberfall.de', 'mamber.net', 'meine-dateien.info', 'meine-diashow.de', 'meine-fotos.info', 'meine-urlaubsfotos.de', 'metaping.com', 'mns.ru', 'mufmail.com', 'muskelshirt.de', 'my-mail.ch', 'myadult.info', 'mytop-in.net', 'mytrashmail.compookmail.com', 'netterchef.de', 'neue-dateien.de', 'neverbox.com', 'nm.ru', 'nospammail.net', 'nur-fuer-spam.de', 'nybella.com', 'office-dateien.de', 'oikrach.com', 'open.by', 'orangatango.com', 'partybombe.de', 'partyheld.de', 'phreaker.net', 'pisem.net', 'pleasedontsendmespam.de', 'polizisten-duzer.de', 'pornobilder-mal-gratis.com', 'portsaid.cc', 'postfach.cc', 'prydirect.info', 'pryworld.info', 'public-files.de', 'qq.com', 'quantentunnel.de', 'qv7.info', 'ralib.com', 'raubtierbaendiger.de', 'record.me', 'recursor.net', 'rootprompt.org', 'saeuferleber.de', 'sags-per-mail.de', 'satka.net', 'schmusemail.de', 'schreib-doch-mal-wieder.de', 'shared-files.de', 'shinedyoureyes.com', 'siria.cc', 'skeefmail.net', 'sms.at', 'sonnenkinder.org', 'spamcannon.com', 'spamcannon.net', 'spameater.com', 'spameater.org', 'spamgourmet.com', 'spamgourmet.net', 'spamgourmet.org', 'spamgrube.net', 'spammote.com', 'spammuffel.de', 'spamreturn.com', 'sperke.net', 'sriaus.com', 'streber24.de', 'sweetville.net', 'tagesmail.eu', 'terminverpennt.de', 'test.com', 'test.de', 'thepryam.info', 'topmail-files.de', 'tortenboxer.de', 'totalmail.de', 'trashbox.eu', 'trimix.cn', 'turboprinz.de', 'turboprinzessin.de', 'tut.by', 'ua.fm', 'uk2.net', 'ukr.net', 'unterderbruecke.de', 'verlass-mich-nicht.de', 'veryrealmail.com', 'vinbazar.com', 'vollbio.de', 'volloeko.de', 'vorsicht-bissig.de', 'vorsicht-scharf.de', 'war-im-urlaub.de', 'wbb3.de', 'webmail4u.eu', 'weibsvolk.de', 'weibsvolk.org', 'weinenvorglueck.de', 'will-hier-weg.de', 'wir-haben-nachwuchs.de', 'wir-sind-cool.org', 'wirsindcool.de', 'wolke7.net', 'women-at-work.org', 'wormseo.cn', 'wp.pl', 'xoxy.net', 'xsecurity.org', 'yandex.ru', 'yesey.net', 'yopweb.com', 'ystea.org', 'yzbid.com', 'zweb.in'
    );
    
    /**
    * Nameservers to use when make DNS query for MX entries
    * @var Array $nameservers
    */
    var $nameservers = array(
        '10.1.1.141',
        '8.8.8.8',
        '8.8.4.4'
    );

    var $debug = false;

    /**
    * Initializes the Class
    * @return SMTP_validateEmail Instance
    * @param $email Array[optional] List of Emails to Validate
    * @param $sender String[optional] Email of validator
    */
    function SMTP_validateEmail($emails = false, $sender = false) {
        if ($emails) {
            $this->setEmails($emails);
        }
        if ($sender) {
            $this->setSenderEmail($sender);
        }
    }

    function _parseEmail($email) {
        $parts = explode('@', $email);
        $domain = array_pop($parts);
        $user = implode('@', $parts);
        return array($user, $domain);
    }

    /**
    * Set the Emails to validate
    * @param $emails Array List of Emails
    */
    function setEmails($emails) {
        foreach($emails as $email) {
            list($user, $domain) = $this->_parseEmail($email);
            if (!isset($this->domains[$domain])) {
                $this->domains[$domain] = array();
            }
            $this->domains[$domain][] = $user;
        }
    }

    /**
    * Set the Email of the sender/validator
    * @param $email String
    */
    function setSenderEmail($email) {
        if ($email) {
            $parts = $this->_parseEmail($email);
            $this->from_user = $parts[0];
            $this->from_domain = $parts[1];
        }
    }

    /**
    * Validate Email Addresses
    * @param String $emails Emails to validate (recipient emails)
    * @param String $sender Sender's Email
    * @return Array Associative List of Emails and their validation results
    */
    function validate($emails = false, $sender = false) {

        $results = array();

        if ($emails) {
            $this->setEmails($emails);
        }
        if ($sender) {
            $this->setSenderEmail($sender);
        }

        // query the MTAs on each Domain
        foreach($this->domains as $domain => $users) {
            
            if (strlen($domain) > 255) {
                foreach($users as $user) {
                    $results[$user.'@'.$domain] = array(false, 'Domain length too long');
                }
                continue;
            }
            elseif (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                foreach($users as $user) {
                    $results[$user.'@'.$domain] = array(false, 'Invalid characters in domain');
                }
                continue;
            }
            elseif (preg_match('/\\.\\./', $domain)) {
                foreach($users as $user) {
                    $results[$user.'@'.$domain] = array(false, 'Invalid domain');
                }
                continue;
            }
            elseif (preg_match('/(^|\.)('.implode('|', $this->deaDomains).')$/', $domain)) {
                foreach($users as $user) {
                    $results[$user.'@'.$domain] = array(false, 'Domain not allowed');
                }
                continue;
            }
            
            
            $mxs = array();

            // current domain being queried
            $this->domain = $domain;

            // retrieve SMTP Server via MX query on domain
            list($hosts, $mxweights) = $this->queryMX($domain);

            // retrieve MX priorities
            for($n = 0; $n < count($hosts); $n++){
                $mxs[$hosts[$n]] = $mxweights[$n];
            }
            asort($mxs);

            // last fallback is the original domain
            $mxs[$this->domain] = 0;

            $this->debug(print_r($mxs, 1));

            $timeout = $this->max_conn_time;

            // try each host
            while(list($host) = each($mxs)) {
                // connect to SMTP server
                $this->debug("try $host:$this->port\n");
                if ($this->sock = @fsockopen($host, $this->port, $errno, $errstr, (float) $timeout)) {
                    stream_set_timeout($this->sock, $this->max_read_time);
                    break;
                }
            }

            // did we get a TCP socket
            if ($this->sock) {
                $reply = fread($this->sock, 2082);
                $this->debug("<<<\n$reply");

                preg_match('/^([0-9]{3}) /ims', $reply, $matches);
                $code = isset($matches[1]) ? $matches[1] : '';

                if($code != '220') {
                    // MTA gave an error...  Not true or false
                    foreach($users as $user) {
                        $results[$user.'@'.$domain] = array(null, 'MTA Error');
                    }
                    continue;
                }

                // say helo
                $this->send("HELO ".$this->from_domain);
                // tell of sender
                $from_reply = $this->send("MAIL FROM: <".$this->from_user.'@'.$this->from_domain.">");
                preg_match('/^([0-9]{3}) /ims', $from_reply, $from_matches);
                $from_code = isset($from_matches[1]) ? $from_matches[1] : '';
                
                // ask for each recepient on this domain
                foreach($users as $user) {
                    
                    // Local checks
                    if (strlen($user) > 64) {
                        // local part length exceeded
                        $results[$user.'@'.$domain] = array(false, 'Username length too long');
                        continue;
                    }
                    elseif (preg_match('/^\\./', $user)) {
                        // local part starts or ends with '.'
                        $results[$user.'@'.$domain] = array(false, 'Username starts with a dot');
                        continue;
                    }
                    elseif (preg_match('/\\.\\./', $user)) {
                        $results[$user.'@'.$domain] = array(false, 'Username has two consecutive dots');
                        continue;
                    }
                    
                    elseif (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $user))) {
                        // character not valid in local part unless
                        // local part is quoted
                        if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $user))) {
                            $results[$user.'@'.$domain] = array(false, 'Invalid characters in username');
                            continue;
                        }
                    }
                    
                    if ($from_code == '421') {
                        // General Problem where the SMTP doesn't want to talk to me
                        $results[$user.'@'.$domain] = array(true);
                    }
                    else {
                        // ask of recepient
                        $reply = $this->send("RCPT TO: <".$user.'@'.$domain.">");
    
                        // get code and msg from response
                        preg_match('/^([0-9]{3}) /ims', $reply, $matches);
                        $code = isset($matches[1]) ? $matches[1] : '';
                        
                        if ($code == '250' || $code == '251' || $code == '252') {
                            // you received 250 so the email address was accepted
                            $results[$user.'@'.$domain] = array(true);
                        } elseif ($code == '421' || $code == '450' || $code == '451' || $code == '452') {
                            // you received 4xx so the email address was greylisted (or some temporary error occured on the MTA) - so assume is ok
                            $results[$user.'@'.$domain] = array(true);
                        } else {
                            $results[$user.'@'.$domain] = array(false);
                        }
                    }
                }

                // reset before quit
                $this->send("RSET");

                // quit
                $this->send("QUIT");
                // close socket
                fclose($this->sock);
            }
            else {
                foreach($users as $user) {
                    $results[$user.'@'.$domain] = array(false, 'MTA Error - No Connection');
                }
            }
            
        }
        
        return $results;
    }


    function send($msg) {
        fwrite($this->sock, $msg."\r\n");

        $reply = fread($this->sock, 2082);

        $this->debug(">>>\n$msg\n");
        $this->debug("<<<\n$reply");

        return $reply;
    }

    /**
    * Query DNS server for MX entries
    * @return
    */
    function queryMX($domain) {
        $hosts = array();
        $mxweights = array();
        if (function_exists('getmxrr')) {
            getmxrr($domain, $hosts, $mxweights);
        } else {
            // windows, we need Net_DNS
            require_once 'Net/DNS.php';

            $resolver = new Net_DNS_Resolver();
            $resolver->debug = $this->debug;
            // nameservers to query
            $resolver->nameservers = $this->nameservers;
            $resp = $resolver->query($domain, 'MX');
            if ($resp) {
                foreach($resp->answer as $answer) {
                    $hosts[] = $answer->exchange;
                    $mxweights[] = $answer->preference;
                }
            }

        }
        return array($hosts, $mxweights);
    }

    /**
    * Simple function to replicate PHP 5 behaviour. http://php.net/microtime
    */
    function microtime_float() {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function debug($str) {
        if ($this->debug) {
            echo '<pre>'.htmlentities($str).'</pre>';
        }
    }


}
