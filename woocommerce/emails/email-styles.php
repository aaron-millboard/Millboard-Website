<?php
/**
 * Email styles, shared by every template.
 *
 * WooCommerce runs Emogrifier over the finished message at send time
 * (WC_Email::style_inline), so these rules are inlined for the client. That is
 * why the templates carry classes and ids rather than repeating inline styles,
 * and why this file must stay the single source rather than being pre-inlined.
 *
 * Ported from the design handoff, with two Outlook corrections, both of which
 * the handoff flagged and neither of which the markup change alone fixed:
 *
 *   1. The outer padding was on `#wrapper`, a div. Outlook ignores padding on a
 *      div, so the email sat edge to edge against the window. It is on the
 *      containing cell, `#wrapper_cell`, instead.
 *   2. The button padding was on the `<a>` with `padding: 0` on its cell.
 *      Outlook ignores padding on an anchor even with display:block, so the
 *      button collapsed to the size of its text. The padding is on the `<td>`.
 *
 * Tokens come from the theme, _src/styles/2-variables/_color-variables.scss:
 * foreground #151716, springwood #f9f7f1, mist #dedcd2, olive #799513,
 * charcoal #575756.
 *
 * @package Millboard
 */

defined( 'ABSPATH' ) || exit;

?>
body { margin: 0; padding: 0; background-color: #dedcd2; -webkit-text-size-adjust: 100%; }
#outer_wrapper { background-color: #dedcd2; width: 100%; }
#wrapper_cell { padding: 40px 16px; }
#wrapper { padding: 0; }
#template_container { background-color: #f9f7f1; width: 600px; max-width: 600px; border-collapse: collapse; }
h1, h2, h3, p, td, th, li, a, span, strong, address, small { font-family: 'F37 Ginger','Helvetica Neue',Helvetica,Arial,sans-serif; }
#template_header_image { padding: 40px 48px 32px 48px; text-align: left; }
#template_header_image img { width: 150px; height: auto; display: block; border: 0; }
#template_rule { padding: 0 48px; }
#template_rule div { border-top: 1px solid #799513; height: 1px; font-size: 0; line-height: 0; }
#template_header_strip { padding: 0; font-size: 0; line-height: 0; }
#template_header_strip img { width: 100%; max-width: 600px; height: auto; display: block; border: 0; }
#body_content_inner { padding: 40px 48px 48px 48px; color: #151716; }
.mb-eyebrow { margin: 0 0 16px 0; font-weight: 700; font-size: 12px; line-height: 18px; letter-spacing: 2px; text-transform: uppercase; color: #151716; }
h1 { margin: 0 0 24px 0; font-weight: 300; font-size: 28px; line-height: 36px; letter-spacing: 4px; text-transform: uppercase; color: #151716; }
p { margin: 0 0 16px 0; font-weight: 300; font-size: 16px; line-height: 26px; color: #151716; }
h2 { margin: 40px 0 16px 0; font-weight: 700; font-size: 12px; line-height: 18px; letter-spacing: 2px; text-transform: uppercase; color: #151716; }
h2 a { color: #151716; text-decoration: none; }
a { color: #151716; text-decoration: underline; }
.mb-note { margin: 0 0 24px 0; padding: 16px 0; border-top: 1px solid #dedcd2; border-bottom: 1px solid #dedcd2; font-size: 14px; line-height: 22px; }
.mb-quote { margin: 0 0 24px 0; padding: 20px 24px; background-color: #dedcd2; font-size: 16px; line-height: 26px; }
.mb-button { border-collapse: collapse; margin: 8px 0 24px 0; }
.mb-button td { background-color: #151716; padding: 16px 32px; }
.mb-button a { display: block; padding: 0; font-weight: 700; font-size: 13px; line-height: 16px; letter-spacing: 2px; text-transform: uppercase; color: #f9f7f1; text-decoration: none; }
.mb-small { font-size: 14px; line-height: 22px; color: #575756; }
.mb-detail { width: 100%; border-collapse: collapse; margin: 0 0 24px 0; }
.mb-detail th { padding: 8px 16px 8px 0; text-align: left; vertical-align: top; font-weight: 700; font-size: 11px; line-height: 22px; letter-spacing: 1.5px; text-transform: uppercase; color: #151716; width: 120px; }
.mb-detail td { padding: 8px 0; font-weight: 300; font-size: 15px; line-height: 22px; color: #151716; }
table.td { width: 100%; border-collapse: collapse; margin: 0 0 8px 0; }
table.td thead th { padding: 0 0 10px 0; text-align: left; font-weight: 700; font-size: 11px; line-height: 16px; letter-spacing: 1.5px; text-transform: uppercase; color: #151716; border-bottom: 1px solid #151716; }
table.td tbody td { padding: 16px 0; vertical-align: top; font-weight: 300; font-size: 15px; line-height: 22px; color: #151716; border-bottom: 1px solid #dedcd2; }
table.td .td-qty { width: 56px; text-align: center; }
table.td .td-price { width: 96px; text-align: right; }
table.td tfoot th { padding: 10px 0; text-align: left; font-weight: 300; font-size: 15px; line-height: 22px; color: #151716; }
table.td tfoot td { padding: 10px 0; text-align: right; font-weight: 300; font-size: 15px; line-height: 22px; color: #151716; }
table.td tfoot tr.order-total th, table.td tfoot tr.order-total td { padding-top: 14px; border-top: 1px solid #151716; font-weight: 700; }
table.td tfoot small { font-weight: 300; font-size: 13px; color: #575756; }
.wc-item-meta { margin: 6px 0 0 0; padding: 0; list-style: none; font-size: 13px; line-height: 20px; color: #575756; }
.wc-item-meta li { margin: 0; padding: 0; font-size: 13px; line-height: 20px; color: #575756; }
.wc-item-meta-label { font-weight: 700; color: #575756; }
#addresses { width: 100%; border-collapse: collapse; margin: 0; }
#addresses td.address-col { vertical-align: top; width: 50%; padding: 0 16px 0 0; }
address { font-style: normal; font-weight: 300; font-size: 15px; line-height: 24px; color: #151716; }
#template_footer { border-collapse: collapse; width: 100%; }
#template_footer td { padding: 32px 48px 40px 48px; border-top: 1px solid #dedcd2; }
#template_footer p { margin: 0 0 12px 0; font-size: 13px; line-height: 20px; color: #575756; }
#template_footer a { color: #151716; text-decoration: underline; }
#template_footer .mb-legal { margin: 0; }
@media screen and (max-width: 620px) {
  #wrapper_cell { padding: 0 !important; }
  #template_header_image, #body_content_inner, #template_footer td { padding-left: 24px !important; padding-right: 24px !important; }
  #template_rule { padding: 0 24px !important; }
  h1 { font-size: 24px !important; line-height: 32px !important; letter-spacing: 3px !important; }
  #addresses td.address-col { display: block !important; width: 100% !important; padding: 0 0 24px 0 !important; }
}
