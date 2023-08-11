import * as React from "react";
const SvgKwKuwait = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="KW_-_Kuwait_svg__a"
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
    <g mask="url(#KW_-_Kuwait_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="KW_-_Kuwait_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#KW_-_Kuwait_svg__b)">
        <path fill="#093" d="M0 0v4h16V0H0Z" />
        <path fill="#E31D1C" d="M0 8v4h16V8H0Z" />
      </g>
      <path
        fill="#272727"
        fillRule="evenodd"
        d="M0 0v12l6-4V4L0 0Z"
        clipRule="evenodd"
      />
      <mask
        id="KW_-_Kuwait_svg__c"
        width={6}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12l6-4V4L0 0Z"
          clipRule="evenodd"
        />
      </mask>
    </g>
  </svg>
);
export default SvgKwKuwait;
