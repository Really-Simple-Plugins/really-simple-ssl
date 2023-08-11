import * as React from "react";
const SvgGeGeorgia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GE_-_Georgia_svg__a"
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
    <g mask="url(#GE_-_Georgia_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="GE_-_Georgia_svg__b"
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
        fill="#E31D1C"
        fillRule="evenodd"
        clipRule="evenodd"
        mask="url(#GE_-_Georgia_svg__b)"
      >
        <path d="M7 0h2v5h7v2H9v5H7V7H0V5h7V0Z" />
        <path d="M5 8.611 6.1 8.5v1S5 9.431 5 9.451c0 .02.1 1.049.1 1.049h-1l.08-1H3.1v-1l1.08.111L4.1 7.5h1L5 8.611ZM5 2.611 6.1 2.5v1S5 3.431 5 3.451c0 .02.1 1.049.1 1.049h-1l.08-1H3.1v-1l1.08.111L4.1 1.5h1L5 2.611ZM12 2.611l1.1-.111v1S12 3.431 12 3.451c0 .02.1 1.049.1 1.049h-1l.08-1H10.1v-1l1.08.111L11.1 1.5h1L12 2.611ZM12 8.611l1.1-.111v1S12 9.431 12 9.451c0 .02.1 1.049.1 1.049h-1l.08-1H10.1v-1l1.08.111L11.1 7.5h1L12 8.611Z" />
      </g>
    </g>
  </svg>
);
export default SvgGeGeorgia;
