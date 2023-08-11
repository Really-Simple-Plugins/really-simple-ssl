import * as React from "react";
const SvgGgGuernsey = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GG_-_Guernsey_svg__a"
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
    <g mask="url(#GG_-_Guernsey_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="GG_-_Guernsey_svg__b"
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
      <g
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#GG_-_Guernsey_svg__b)"
      >
        <path fill="#E31D1C" d="M6 0h4v4h6v4h-6v4H6V8H0V4h6V0Z" />
        <path
          fill="#FECA00"
          d="M6.503 1.523 7 2.4V5H3.433v-.029l-.88-.498v2.98L3.385 7H7v2.525l-.497.878h2.98l-.479-.88H9V7h3.6l.833.453v-2.98l-.88.498V5H9V2.403h.004l.479-.88h-2.98Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgGgGuernsey;
