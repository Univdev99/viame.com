\c arthur
DROP DATABASE IF EXISTS viame;
CREATE DATABASE viame WITH TEMPLATE = template0 OWNER = vmdbuser ENCODING = 'UTF8';
\c viame

DROP LANGUAGE plpgsql CASCADE;
CREATE LANGUAGE plpgsql;

DROP LANGUAGE plperl CASCADE;
CREATE LANGUAGE plperl;

\i ../system/system_functions.sql

\i ../system/system_sessions.sql

\i ../system/system_countries.sql
\i ../system/system_languages.sql
\i ../system/system_timezones.sql
\i ../system/system_currencies.sql

\i ../member/member_members.sql
\i ../member/member_openids.sql

\i ../module/module_modules.sql

\i ../system/system_communities.sql

\i ../profile/profile_profiles.sql
\i ../profile/profile_follow_matrix.sql

\i ../network/network_networks.sql
\i ../network/network_members.sql

\i ../module/module_matrix.sql

\i ../log/log_logs.sql
\i ../log/log_trans.sql

\i ../contact/contact_contacts.sql
\i ../contact/contact_group_groups.sql
\i ../contact/contact_group_members.sql

\i ../acl/acl_acls.sql
\i ../acl/acl_passwords.sql
\i ../acl/acl_members.sql

\i ../mail/mail_stats.sql
\i ../mail/mail_mails.sql
\i ../mail/mail_folder_folders.sql
\i ../mail/mail_folder_matrix.sql

\i ../system/system_comments.sql
\i ../system/system_ratings.sql

--\i ../system/system_view_count_matrix.sql
--\i ../system/system_view_count_counts.sql

\i ../company/company_industries.sql
\i ../company/company_sectors.sql
\i ../company/company_companies.sql
\i ../company/company_locations.sql

\i ../quote/quote_types.sql
\i ../quote/quote_exchanges.sql
\i ../quote/quote_symbols.sql
\i ../quote/quote_data.sql
\i ../quote/quote_views.sql
\i ../quote/quote_comments.sql
\i ../quote/quote_follow_matrix.sql

\i ../widget/widget_widgets.sql
\i ../widget/widget_matrix.sql

\i ../widget/widget_widgets/widget_widgets_feed.sql

\i ../module/module_template.sql
\i ../module/module_views_counts.sql


\i ../file/file_files.sql
\i ../page/page_pages.sql
--\i ../info/info_infos.sql
\i ../article/article_articles.sql
\i ../analysis/analysis_analysiss.sql
\i ../portfolio/portfolio_portfolios.sql
\i ../portfolio/portfolio_positions.sql
\i ../pick/pick_picks.sql
\i ../message/message_messages.sql
\i ../blog/blog_blogs.sql

\i ../system/last.sql
