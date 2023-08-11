import * as React from "react";
const SvgLsLesotho = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="LS_-_Lesotho_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g fillRule="evenodd" clipRule="evenodd" mask="url(#LS_-_Lesotho_svg__a)">
      <path fill="#55BA07" d="M0 8h16v4H0V8Z" />
      <path fill="#F7FCFF" d="M0 4h16v4H0V4Z" />
      <path fill="#3D58DB" d="M0 0h16v4H0V0Z" />
      <path
        fill="#1D1D1D"
        d="M7.625 4.12c-.261.07-.4.25-.4.57 0 .366.18.708.4.885V4.12Zm.625 1.445c.214-.179.386-.515.386-.874 0-.31-.136-.49-.386-.564v1.438Zm-.021-1.699c.41.091.705.37.705.823 0 .329-.156.73-.4 1.004l1.218 1.193.3-.098.448.68S9.367 8 7.967 8 5.5 7.467 5.5 7.467l.382-.58.308.1 1.129-1.273c-.254-.275-.418-.688-.418-1.026 0-.468.317-.75.749-.83a.313.313 0 0 1 .579.008Z"
      />
    </g>
  </svg>
);
export default SvgLsLesotho;
