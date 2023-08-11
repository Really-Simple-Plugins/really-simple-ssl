import * as React from "react";
const SvgNrNauru = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="NR_-_Nauru_svg__a"
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
    <g mask="url(#NR_-_Nauru_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="NR_-_Nauru_svg__b"
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
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#NR_-_Nauru_svg__b)">
        <path fill="#FECA00" d="M0 4v2h16V4H0Z" />
        <path
          fill="#F7FCFF"
          d="m4.415 9.79-.773 1.003-.035-1.266-1.215.357.715-1.044-1.192-.425 1.192-.425-.715-1.045 1.215.357.035-1.265.773 1.003.772-1.003.036 1.265 1.214-.357-.714 1.045 1.192.425-1.192.425.714 1.044-1.214-.357-.036 1.266-.772-1.003Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgNrNauru;
